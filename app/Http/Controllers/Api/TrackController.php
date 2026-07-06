<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conference\StoreTrackRequest;
use App\Http\Requests\Conference\UpdateTrackRequest;
use App\Http\Resources\TrackResource;
use App\Models\Event;
use App\Models\Track;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class TrackController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return ApiResponse::success(
            TrackResource::collection($event->tracks()->orderBy('sort_order')->get()),
        );
    }

    public function store(StoreTrackRequest $request, Event $event): JsonResponse
    {
        $this->authorize('create', Track::class);
        $this->authorize('update', $event);

        $track = $event->tracks()->create([
            ...$request->validated(),
            'organization_id' => $event->organization_id,
        ]);

        return ApiResponse::created(new TrackResource($track));
    }

    public function update(UpdateTrackRequest $request, Event $event, Track $track): JsonResponse
    {
        $this->authorize('update', $track);
        abort_unless($track->event_id === $event->id, 404);
        $track->update($request->validated());

        return ApiResponse::success(new TrackResource($track->fresh()));
    }

    public function destroy(Event $event, Track $track): JsonResponse
    {
        $this->authorize('delete', $track);
        abort_unless($track->event_id === $event->id, 404);
        $track->delete();

        return ApiResponse::success(message: 'Track deleted.');
    }
}
