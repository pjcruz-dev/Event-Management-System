<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'subtotal' => (float) $this->subtotal,
            'discount_total' => (float) $this->discount_total,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'refund_amount' => $this->refund_amount !== null ? (float) $this->refund_amount : null,
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'refund_reason' => $this->refund_reason,
            'registration_id' => $this->registration_id,
            'registrations' => $this->whenLoaded('registrations', fn () => $this->registrations->map(fn ($r) => [
                'id' => $r->id,
                'registration_number' => $r->registration_number,
                'attendee_name' => trim($r->attendee_first_name.' '.($r->attendee_last_name ?? '')),
                'attendee_email' => $r->attendee_email,
                'status' => $r->status->value,
                'ticket_type' => $r->ticketType?->name,
            ])),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
