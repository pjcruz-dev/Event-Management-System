<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EventTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventTable */
final class EventTableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assignedCount = $this->assignedCount();

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'sort_order' => $this->sort_order,
            'shape' => $this->shape?->value,
            'x' => $this->x,
            'y' => $this->y,
            'rotation' => $this->rotation,
            'assigned_count' => $assignedCount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
