<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketReservationStatus;
use App\Models\TicketReservation;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;

final class TicketAvailabilityService
{
    public function remainingQuantity(TicketType $ticketType): ?int
    {
        if ($ticketType->quantity === null) {
            return null;
        }

        $reserved = $this->activeReservedQuantity($ticketType);

        return max(0, $ticketType->quantity - $ticketType->quantity_sold - $reserved);
    }

    public function activeReservedQuantity(TicketType $ticketType): int
    {
        return (int) TicketReservation::withoutTenantScope('ticket availability check')
            ->where('ticket_type_id', $ticketType->id)
            ->where('status', TicketReservationStatus::Active)
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    public function reserve(
        TicketType $ticketType,
        int $quantity,
        ?int $orderId = null,
    ): TicketReservation {
        return DB::transaction(function () use ($ticketType, $quantity, $orderId): TicketReservation {
            $locked = TicketType::query()
                ->whereKey($ticketType->id)
                ->lockForUpdate()
                ->firstOrFail();

            $remaining = $this->remainingQuantity($locked);

            if ($remaining !== null && $quantity > $remaining) {
                throw new \RuntimeException('Insufficient ticket availability.');
            }

            $minutes = (int) config('ticketing.reservation_minutes', 15);

            return TicketReservation::query()->create([
                'organization_id' => $locked->organization_id,
                'event_id' => $locked->event_id,
                'ticket_type_id' => $locked->id,
                'order_id' => $orderId,
                'quantity' => $quantity,
                'status' => TicketReservationStatus::Active,
                'expires_at' => now()->addMinutes($minutes),
            ]);
        });
    }

    public function releaseForOrder(int $orderId): void
    {
        TicketReservation::query()
            ->where('order_id', $orderId)
            ->where('status', TicketReservationStatus::Active)
            ->update(['status' => TicketReservationStatus::Released]);
    }

    public function releaseExpiredReservations(): int
    {
        return TicketReservation::query()
            ->where('status', TicketReservationStatus::Active)
            ->where('expires_at', '<=', now())
            ->update(['status' => TicketReservationStatus::Released]);
    }

    public function isOnSale(TicketType $ticketType): bool
    {
        if (! $ticketType->is_active) {
            return false;
        }

        if ($ticketType->sales_starts_at !== null && $ticketType->sales_starts_at->isFuture()) {
            return false;
        }

        if ($ticketType->sales_ends_at !== null && $ticketType->sales_ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
