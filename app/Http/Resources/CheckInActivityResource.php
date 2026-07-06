<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityLog */
final class CheckInActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'metadata' => $this->metadata,
            'actor' => $this->actor?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
