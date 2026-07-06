<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Speaker */
final class SpeakerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(StorageService::class);

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'title' => $this->title,
            'bio' => $this->bio,
            'photo_path' => $this->photo_path,
            'photo_url' => $this->photo_path
                ? $storage->url($this->photo_path, 'public')
                : null,
            'social_links' => $this->social_links ?? [],
            'sort_order' => $this->sort_order,
            'event_sessions' => EventSessionResource::collection($this->whenLoaded('eventSessions')),
        ];
    }
}
