<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TicketType;
use App\Services\TicketAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketType */
final class TicketTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $availability = app(TicketAvailabilityService::class);

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'type_tag' => $this->type_tag,
            'description' => $this->description,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'quantity' => $this->quantity,
            'quantity_sold' => $this->quantity_sold,
            'remaining_quantity' => $availability->remainingQuantity($this->resource),
            'per_order_limit' => $this->per_order_limit,
            'sales_starts_at' => $this->sales_starts_at?->toIso8601String(),
            'sales_ends_at' => $this->sales_ends_at?->toIso8601String(),
            'visibility' => $this->visibility?->value,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'is_on_sale' => $availability->isOnSale($this->resource),
        ];
    }
}
