<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EventSession */
final class EventSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'track_id' => $this->track_id,
            'title' => $this->title,
            'description' => $this->description,
            'room' => $this->room,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'capacity' => $this->capacity,
            'registered_count' => $this->whenCounted('sessionRegistrations'),
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'track' => new TrackResource($this->whenLoaded('track')),
            'speakers' => SpeakerResource::collection($this->whenLoaded('speakers')),
        ];
    }
}
