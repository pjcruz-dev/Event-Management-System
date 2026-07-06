<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTO\Registration\RegistrationCheckoutResult;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RegistrationCheckoutResult */
final class RegistrationCheckoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var RegistrationCheckoutResult $result */
        $result = $this->resource;

        if ($result->isWaitingList()) {
            return [
                'status' => 'waitlisted',
                'waiting_list_entry' => [
                    'id' => $result->waitingListEntry?->id,
                    'ticket_type_id' => $result->waitingListEntry?->ticket_type_id,
                    'attendee_email' => $result->waitingListEntry?->attendee_email,
                ],
            ];
        }

        return [
            'status' => $result->order?->status === OrderStatus::Paid ? 'confirmed' : 'pending_payment',
            'registration' => [
                'id' => $result->registration?->id,
                'registration_number' => $result->registration?->registration_number,
                'attendee_email' => $result->registration?->attendee_email,
            ],
            'order' => [
                'id' => $result->order?->id,
                'order_number' => $result->order?->order_number,
                'status' => $result->order?->status?->value,
                'subtotal' => (float) $result->order?->subtotal,
                'discount_total' => (float) $result->order?->discount_total,
                'total' => (float) $result->order?->total,
                'currency' => $result->order?->currency,
                'items' => $result->order?->items->map(fn ($item) => [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                ]),
            ],
        ];
    }
}
