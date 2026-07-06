<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestInvite\StoreGuestInviteRequest;
use App\Http\Requests\GuestInvite\UpdateGuestInviteRequest;
use App\Http\Resources\GuestInviteResource;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Notifications\RsvpReminderNotification;
use App\Services\GuestInviteService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;

final class GuestInviteController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', GuestInvite::class);

        $query = GuestInvite::query()
            ->where('event_id', $event->id)
            ->with(['table', 'registration']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('rsvp_response')) {
            $query->where('rsvp_response', $request->string('rsvp_response'));
        }

        if ($request->filled('tag')) {
            $tag = $request->string('tag');
            $query->whereJsonContains('tags', (string) $tag);
        }

        if ($request->filled('table_id')) {
            $query->where('table_id', $request->integer('table_id'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('email', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search);
            });
        }

        $invites = $query->orderByDesc('created_at')->paginate(
            min($request->integer('per_page', 25), 100),
        );

        return ApiResponse::success([
            'items' => GuestInviteResource::collection($invites->items()),
            'pagination' => [
                'current_page' => $invites->currentPage(),
                'per_page' => $invites->perPage(),
                'total' => $invites->total(),
                'last_page' => $invites->lastPage(),
            ],
        ]);
    }

    public function store(StoreGuestInviteRequest $request, Event $event, GuestInviteService $service): JsonResponse
    {
        $this->authorize('create', GuestInvite::class);
        $this->authorize('update', $event);

        $invite = $service->create($event, $request->validated());

        return ApiResponse::success(
            new GuestInviteResource($invite->load(['table', 'registration'])),
            'Guest invite created.',
            201,
        );
    }

    public function update(
        UpdateGuestInviteRequest $request,
        Event $event,
        GuestInvite $guestInvite,
        GuestInviteService $service,
    ): JsonResponse {
        $this->ensureBelongsToEvent($event, $guestInvite);
        $this->authorize('update', $guestInvite);

        $invite = $service->update($guestInvite, $request->validated());

        return ApiResponse::success(new GuestInviteResource($invite));
    }

    public function destroy(Event $event, GuestInvite $guestInvite, GuestInviteService $service): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $guestInvite);
        $this->authorize('delete', $guestInvite);

        $service->revoke($guestInvite);

        return ApiResponse::success(null, 'Guest invite revoked.');
    }

    public function send(Event $event, GuestInvite $guestInvite, GuestInviteService $service): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $guestInvite);
        $this->authorize('update', $guestInvite);

        $invite = $service->send($guestInvite);

        return ApiResponse::success(new GuestInviteResource($invite), 'Invitation email queued.');
    }

    public function sendBulk(Request $request, Event $event, GuestInviteService $service): JsonResponse
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'invite_ids' => ['nullable', 'array'],
            'invite_ids.*' => ['integer'],
        ]);

        $result = $service->sendBulk($event, $data['invite_ids'] ?? []);

        return ApiResponse::success($result, 'Bulk invitations queued.');
    }

    public function import(Request $request, Event $event, GuestInviteService $service): JsonResponse
    {
        $this->authorize('update', $event);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $rows = $this->parseCsv($request->file('file')->getRealPath());
        $result = $service->importRows($event, $rows);

        return ApiResponse::success($result, 'Import completed.');
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(static fn ($col) => strtolower(trim((string) $col)), $header);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, static fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = isset($data[$index]) ? trim((string) $data[$index]) : null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    public function remind(Event $event, GuestInvite $guestInvite): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $guestInvite);
        $this->authorize('update', $guestInvite);

        if ($guestInvite->rsvp_response !== null) {
            return ApiResponse::error('Guest has already responded.', status: 422);
        }

        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $guestInvite->email);
        $notifiable->notify(new RsvpReminderNotification($guestInvite));

        $guestInvite->update([
            'last_reminder_sent_at' => now(),
            'reminder_count' => $guestInvite->reminder_count + 1,
        ]);

        return ApiResponse::success(
            new GuestInviteResource($guestInvite->fresh(['table', 'registration'])),
            'Reminder sent.',
        );
    }

    public function remindAll(Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $invites = GuestInvite::query()
            ->where('event_id', $event->id)
            ->whereIn('status', ['sent', 'opened'])
            ->whereNull('rsvp_response')
            ->get();

        $sent = 0;
        foreach ($invites as $invite) {
            $notifiable = new AnonymousNotifiable;
            $notifiable->route('mail', $invite->email);
            $notifiable->notify(new RsvpReminderNotification($invite));

            $invite->update([
                'last_reminder_sent_at' => now(),
                'reminder_count' => $invite->reminder_count + 1,
            ]);
            $sent++;
        }

        return ApiResponse::success(['sent' => $sent], "Sent {$sent} reminder(s).");
    }

    private function ensureBelongsToEvent(Event $event, GuestInvite $guestInvite): void
    {
        if ($guestInvite->event_id !== $event->id) {
            abort(404);
        }
    }
}
