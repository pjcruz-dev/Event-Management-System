<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExhibitorResource;
use App\Http\Resources\EventSessionResource;
use App\Http\Resources\SpeakerResource;
use App\Http\Resources\SponsorResource;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Exhibitor;
use App\Models\Registration;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\Track;
use App\Services\PublicEventAccessService;
use App\Services\SessionRegistrationService;
use App\Services\TenantContext;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PublicConferenceController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PublicEventAccessService $publicEventAccess,
    ) {}

    public function agenda(Request $request, string $slug): JsonResponse
    {
        $event = $this->resolveEvent($request, $slug);

        $tracks = Track::query()
            ->where('event_id', $event->id)
            ->orderBy('sort_order')
            ->get();
        $sessions = EventSession::query()
            ->where('event_id', $event->id)
            ->where('is_published', true)
            ->with(['track', 'speakers'])
            ->withCount('sessionRegistrations')
            ->orderBy('starts_at')
            ->get();

        return ApiResponse::success([
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'tracks' => $tracks,
            'sessions' => EventSessionResource::collection($sessions),
            'sponsors' => SponsorResource::collection($this->eventSponsors($event)),
        ]);
    }

    public function speakers(Request $request, string $slug): JsonResponse
    {
        $event = $this->resolveEvent($request, $slug);

        $speakers = Speaker::query()
            ->where('event_id', $event->id)
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(SpeakerResource::collection($speakers));
    }

    public function sponsors(Request $request, string $slug): JsonResponse
    {
        $event = $this->resolveEvent($request, $slug);

        return ApiResponse::success(
            SponsorResource::collection($this->eventSponsors($event)),
        );
    }

    public function exhibitors(Request $request, string $slug): JsonResponse
    {
        $event = $this->resolveEvent($request, $slug);

        $exhibitors = Exhibitor::query()
            ->where('event_id', $event->id)
            ->with('booth')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(ExhibitorResource::collection($exhibitors));
    }

    public function speaker(Request $request, string $slug, Speaker $speaker): JsonResponse
    {
        $event = $this->resolveEvent($request, $slug);
        abort_unless($speaker->event_id === $event->id, 404);

        $speaker->load(['eventSessions' => fn ($q) => $q->where('is_published', true)]);

        return ApiResponse::success(new SpeakerResource($speaker));
    }

    public function registerForSession(
        Request $request,
        string $slug,
        EventSession $session,
        SessionRegistrationService $sessionRegistrationService,
    ): JsonResponse {
        $event = $this->resolveEvent($request, $slug);
        abort_unless($session->event_id === $event->id, 404);

        $data = $request->validate([
            'registration_number' => ['required', 'string', 'max:100'],
            'attendee_email' => ['required', 'email', 'max:255'],
        ]);

        $registration = Registration::withoutTenantScope('public session signup')
            ->where('event_id', $event->id)
            ->where('registration_number', $data['registration_number'])
            ->where('attendee_email', $data['attendee_email'])
            ->first();

        if ($registration === null) {
            throw ValidationException::withMessages([
                'registration_number' => ['Registration not found for this event.'],
            ]);
        }

        $record = $sessionRegistrationService->register($event, $session, $registration);

        return ApiResponse::created([
            'session_registration_id' => $record->id,
            'event_session_id' => $record->event_session_id,
        ], 'Session registration confirmed.');
    }

    private function resolveEvent(Request $request, string $slug): Event
    {
        $event = $this->publicEventAccess->resolveBySlug(
            $slug,
            $this->publicEventAccess->extractPreviewToken(
                $request->query('preview_token'),
                $request->query('token'),
            ),
        );

        $this->tenantContext->set($event->organization);

        return $event;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Sponsor>
     */
    private function eventSponsors(Event $event)
    {
        return Sponsor::query()
            ->where('event_id', $event->id)
            ->orderBy('sort_order')
            ->get();
    }
}
