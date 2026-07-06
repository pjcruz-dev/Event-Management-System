<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckIn\ScanCheckInRequest;
use App\Http\Requests\CheckIn\SyncBatchCheckInRequest;
use App\Http\Resources\BatchSyncItemResource;
use App\Http\Resources\CheckInActivityResource;
use App\Http\Resources\CheckInScanResource;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Registration;
use App\Services\BadgeService;
use App\Services\CertificateService;
use App\Services\CheckInService;
use App\Services\TicketPdfService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckInController extends Controller
{
    public function scan(
        ScanCheckInRequest $request,
        Event $event,
        CheckInService $checkInService,
    ): JsonResponse {
        $this->authorize('view', $event);

        $result = $checkInService->scan(
            $event,
            $request->validated('token'),
            $request->user(),
            $request->only(['device_id', 'gate', 'latitude', 'longitude']),
        );

        $status = match ($result->status) {
            'success' => 200,
            'duplicate' => 409,
            default => 422,
        };

        return ApiResponse::success(
            new CheckInScanResource($result),
            $result->reason,
            $status,
        );
    }

    public function syncBatch(
        SyncBatchCheckInRequest $request,
        Event $event,
        CheckInService $checkInService,
    ): JsonResponse {
        $this->authorize('view', $event);

        $results = $checkInService->syncBatch(
            $event,
            $request->user(),
            $request->validated('items'),
        );

        return ApiResponse::success([
            'items' => BatchSyncItemResource::collection($results),
        ]);
    }

    public function search(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 2) {
            return ApiResponse::success([]);
        }

        $registrations = Registration::query()
            ->where('event_id', $event->id)
            ->where('status', \App\Enums\RegistrationStatus::Confirmed)
            ->where(function ($query) use ($q): void {
                $query->where('attendee_email', 'like', "%{$q}%")
                    ->orWhere('attendee_first_name', 'like', "%{$q}%")
                    ->orWhere('attendee_last_name', 'like', "%{$q}%")
                    ->orWhere('registration_number', 'like', "%{$q}%");
            })
            ->with('ticketType')
            ->limit(20)
            ->get();

        $results = $registrations->map(fn (Registration $r) => [
            'id' => $r->id,
            'registration_number' => $r->registration_number,
            'attendee_name' => trim($r->attendee_first_name.' '.$r->attendee_last_name),
            'attendee_email' => $r->attendee_email,
            'ticket_type' => $r->ticketType?->name,
            'checked_in_at' => $r->checked_in_at?->toIso8601String(),
            'has_qr' => $r->qr_token_hash !== null,
        ]);

        return ApiResponse::success($results);
    }

    public function manualCheckIn(
        Request $request,
        Event $event,
        Registration $registration,
        CheckInService $checkInService,
    ): JsonResponse {
        $this->authorize('view', $event);
        $this->assertRegistrationBelongsToEvent($event, $registration);

        $result = $checkInService->manualCheckIn(
            $registration,
            $request->user(),
            $request->only(['gate']),
        );

        $status = match ($result->status) {
            'success' => 200,
            'duplicate' => 409,
            default => 422,
        };

        return ApiResponse::success(
            new CheckInScanResource($result),
            $result->reason,
            $status,
        );
    }

    public function activity(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $logs = ActivityLog::query()
            ->where('organization_id', $event->organization_id)
            ->whereIn('action', [
                'checkin.scan.success',
                'checkin.scan.duplicate',
                'checkin.scan.rejected',
            ])
            ->where('metadata->event_id', $event->id)
            ->with('actor')
            ->latest()
            ->paginate(min(100, max(1, (int) $request->integer('per_page', 25))));

        return ApiResponse::paginated($logs->through(
            fn (ActivityLog $log) => new CheckInActivityResource($log),
        ));
    }

    public function ticket(
        Event $event,
        Registration $registration,
        TicketPdfService $ticketPdfService,
        CheckInService $checkInService,
    ): Response {
        $this->authorize('view', $event);
        $this->assertRegistrationBelongsToEvent($event, $registration);

        $path = sprintf(
            'tickets/%d/%d/%d.pdf',
            $registration->organization_id,
            $registration->event_id,
            $registration->id,
        );

        $contents = $ticketPdfService->contents($path);

        if ($contents === null) {
            abort(404, 'Ticket PDF not found.');
        }

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket-'.$registration->registration_number.'.pdf"',
        ]);
    }

    public function badge(
        Event $event,
        Registration $registration,
        BadgeService $badgeService,
    ): Response {
        $this->authorize('view', $event);
        $this->assertRegistrationBelongsToEvent($event, $registration);

        $path = sprintf(
            'badges/%d/%d/%d.pdf',
            $registration->organization_id,
            $registration->event_id,
            $registration->id,
        );

        if (! \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->exists($path)) {
            $path = $badgeService->generate($registration);
        }

        $contents = $badgeService->contents($path);

        if ($contents === null) {
            abort(404, 'Badge PDF not found.');
        }

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="badge-'.$registration->registration_number.'.pdf"',
        ]);
    }

    public function issueCertificate(
        Event $event,
        Registration $registration,
        CertificateService $certificateService,
    ): JsonResponse {
        $this->authorize('update', $event);
        $this->assertRegistrationBelongsToEvent($event, $registration);

        $certificate = $certificateService->issue($registration);

        return ApiResponse::created([
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'issued_at' => $certificate->issued_at?->toIso8601String(),
            'file_path' => $certificate->file_path,
        ], 'Certificate issued.');
    }

    private function assertRegistrationBelongsToEvent(Event $event, Registration $registration): void
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }
    }
}
