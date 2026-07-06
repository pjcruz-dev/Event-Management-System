<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conference\StoreEventSessionRequest;
use App\Http\Requests\Conference\UpdateEventSessionRequest;
use App\Http\Resources\EventSessionResource;
use App\Models\Event;
use App\Models\EventSession;
use App\Services\SessionConflictService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class EventSessionController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $sessions = $event->eventSessions()
            ->with(['track', 'speakers'])
            ->withCount('sessionRegistrations')
            ->orderBy('starts_at')
            ->get();

        return ApiResponse::success(EventSessionResource::collection($sessions));
    }

    public function store(
        StoreEventSessionRequest $request,
        Event $event,
        SessionConflictService $conflictService,
    ): JsonResponse {
        $this->authorize('create', EventSession::class);
        $this->authorize('update', $event);

        $data = $request->validated();
        $speakers = $data['speakers'] ?? null;
        unset($data['speakers']);

        $session = $event->eventSessions()->create([
            ...$data,
            'organization_id' => $event->organization_id,
        ]);

        $this->syncSpeakers($session, $speakers);
        $session->load(['track', 'speakers']);

        return ApiResponse::created([
            'session' => new EventSessionResource($session),
            'warnings' => $conflictService->detect($session),
        ]);
    }

    public function update(
        UpdateEventSessionRequest $request,
        Event $event,
        EventSession $session,
        SessionConflictService $conflictService,
    ): JsonResponse {
        $this->authorize('update', $session);
        abort_unless($session->event_id === $event->id, 404);

        $data = $request->validated();
        $speakers = $data['speakers'] ?? null;
        unset($data['speakers']);

        $session->update($data);

        if ($speakers !== null) {
            $this->syncSpeakers($session, $speakers);
        }

        $session->load(['track', 'speakers']);

        return ApiResponse::success([
            'session' => new EventSessionResource($session->fresh()->load(['track', 'speakers'])),
            'warnings' => $conflictService->detect($session->fresh()->load('speakers')),
        ]);
    }

    public function destroy(Event $event, EventSession $session): JsonResponse
    {
        $this->authorize('delete', $session);
        abort_unless($session->event_id === $event->id, 404);
        $session->delete();

        return ApiResponse::success(message: 'Session deleted.');
    }

    /**
     * @param  array<int, array{id: int, role?: string}>|null  $speakers
     */
    private function syncSpeakers(EventSession $session, ?array $speakers): void
    {
        if ($speakers === null) {
            return;
        }

        $sync = [];
        foreach ($speakers as $index => $speaker) {
            $sync[$speaker['id']] = [
                'role' => $speaker['role'] ?? 'speaker',
                'sort_order' => $index,
            ];
        }

        $session->speakers()->sync($sync);
    }
}
