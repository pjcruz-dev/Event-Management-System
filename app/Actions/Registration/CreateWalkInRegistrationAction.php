<?php

declare(strict_types=1);

namespace App\Actions\Registration;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Events\RegistrationConfirmed;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateWalkInRegistrationAction
{
    /**
     * @param  array{
     *   first_name: string,
     *   last_name: string,
     *   email: string,
     *   phone?: string|null,
     *   gate?: string|null,
     *   ticket_type_id?: int|null,
     *   payment_collected?: bool,
     * }  $data
     */
    public function handle(Event $event, array $data, User $staff): Registration
    {
        if ($event->status !== EventStatus::Published) {
            throw ValidationException::withMessages([
                'event' => ['Walk-ins are only allowed for published events.'],
            ]);
        }

        $ticketType = $this->resolveTicketType($event, $data['ticket_type_id'] ?? null);
        $isPaid = (float) $ticketType->price > 0;

        if ($isPaid && empty($data['payment_collected'])) {
            throw ValidationException::withMessages([
                'payment_collected' => [
                    "This ticket costs {$ticketType->currency} {$ticketType->price}. Confirm that payment has been collected.",
                ],
            ]);
        }

        $registration = Registration::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => $data['first_name'],
            'attendee_last_name' => $data['last_name'],
            'attendee_email' => strtolower($data['email']),
            'attendee_phone' => $data['phone'] ?? null,
            'custom_fields' => ['source' => 'walk-in'],
            'checked_in_at' => now(),
            'checked_in_by' => $staff->id,
            'check_in_gate' => $data['gate'] ?? null,
        ]);

        if ($isPaid) {
            $this->createPaidOrder($event, $registration, $ticketType, $staff);
        }

        event(new RegistrationConfirmed($registration));

        return $registration;
    }

    private function createPaidOrder(Event $event, Registration $registration, TicketType $ticketType, User $staff): void
    {
        $total = (float) $ticketType->price;

        $order = Order::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'user_id' => $staff->id,
            'registration_id' => $registration->id,
            'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
            'status' => OrderStatus::Paid,
            'subtotal' => $total,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => $total,
            'currency' => $ticketType->currency,
            'payment_method' => 'on-site',
            'paid_at' => now(),
        ]);

        $registration->update(['order_id' => $order->id]);

        OrderItem::query()->create([
            'organization_id' => $event->organization_id,
            'order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'registration_id' => $registration->id,
            'description' => $ticketType->name.' (walk-in)',
            'quantity' => 1,
            'unit_price' => $total,
            'total_price' => $total,
        ]);
    }

    private function resolveTicketType(Event $event, ?int $ticketTypeId): TicketType
    {
        $query = TicketType::query()
            ->where('event_id', $event->id)
            ->where('is_active', true);

        if ($ticketTypeId !== null) {
            $ticketType = (clone $query)->where('id', $ticketTypeId)->first();

            if ($ticketType === null) {
                throw ValidationException::withMessages([
                    'ticket_type_id' => ['Selected ticket type is not available.'],
                ]);
            }

            return $ticketType;
        }

        $ticketType = $query->orderBy('sort_order')->first();

        if ($ticketType === null) {
            throw ValidationException::withMessages([
                'event' => ['No active ticket types available for walk-in registration.'],
            ]);
        }

        return $ticketType;
    }
}
