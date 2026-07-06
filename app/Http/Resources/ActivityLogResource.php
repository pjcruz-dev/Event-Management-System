<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ActivityLog */
final class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'metadata' => $this->metadata ?? [],
            'actor' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'actor_id' => $this->actor_id,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
