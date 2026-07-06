<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpeakerResource;
use App\Models\Event;
use App\Models\Speaker;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SpeakerController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return ApiResponse::success(
            SpeakerResource::collection($event->speakers()->orderBy('sort_order')->get()),
        );
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('create', Speaker::class);
        $this->authorize('update', $event);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', 'max:500'],
            'social_links' => ['nullable', 'array'],
            'social_links.twitter' => ['nullable', 'string', 'max:255'],
            'social_links.linkedin' => ['nullable', 'string', 'max:255'],
            'social_links.website' => ['nullable', 'url', 'max:500'],
            'social_links.github' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $speaker = $event->speakers()->create([
            ...$data,
            'organization_id' => $event->organization_id,
        ]);

        return ApiResponse::created(new SpeakerResource($speaker));
    }

    public function update(Request $request, Event $event, Speaker $speaker): JsonResponse
    {
        $this->authorize('update', $speaker);
        abort_unless($speaker->event_id === $event->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', 'max:500'],
            'social_links' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $speaker->update($data);

        return ApiResponse::success(new SpeakerResource($speaker->fresh()));
    }

    public function destroy(Event $event, Speaker $speaker): JsonResponse
    {
        $this->authorize('delete', $speaker);
        abort_unless($speaker->event_id === $event->id, 404);
        $speaker->delete();

        return ApiResponse::success(message: 'Speaker deleted.');
    }
}
