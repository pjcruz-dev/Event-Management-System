<?php

declare(strict_types=1);

namespace App\Actions\Registration;

use App\Actions\BaseAction;
use App\Actions\Order\FulfillPaidOrderAction;
use App\DTO\Registration\RegistrationCheckoutResult;
use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Enums\WaitingListStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Notifications\RegistrationConfirmationNotification;
use App\Services\CouponService;
use App\Services\TenantContext;
use App\Services\TicketAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateRegistrationAction extends BaseAction
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TicketAvailabilityService $ticketAvailability,
        private readonly CouponService $couponService,
        private readonly FulfillPaidOrderAction $fulfillPaidOrderAction,
    ) {}

    /**
     * @param  array{
     *   ticket_type_id: int,
     *   quantity: int,
     *   attendee_first_name: string,
     *   attendee_last_name: string,
     *   attendee_email: string,
     *   attendee_phone?: string|null,
     *   custom_fields?: array<string, mixed>|null,
     *   coupon_code?: string|null,
     * }  $data
     */
    public function handle(mixed ...$args): RegistrationCheckoutResult
    {
        /** @var Event $event */
        $event = $args[0];
        /** @var array<string, mixed> $data */
        $data = $args[1];
        /** @var User|null $user */
        $user = $args[2] ?? null;

        $this->assertEventRegisterable($event);

        $this->tenantContext->set($event->organization);

        $ticketType = TicketType::query()
            ->where('event_id', $event->id)
            ->whereKey($data['ticket_type_id'])
            ->firstOrFail();

        $quantity = (int) $data['quantity'];

        if ($quantity < 1 || $quantity > $ticketType->per_order_limit) {
            throw ValidationException::withMessages([
                'quantity' => ['Invalid ticket quantity for this ticket type.'],
            ]);
        }

        if (! $this->ticketAvailability->isOnSale($ticketType)) {
            throw ValidationException::withMessages([
                'ticket_type_id' => ['This ticket type is not currently on sale.'],
            ]);
        }

        $remaining = $this->ticketAvailability->remainingQuantity($ticketType);

        if ($remaining !== null && $quantity > $remaining) {
            return $this->createWaitingListEntry($event, $ticketType, $data, $user);
        }

        return DB::transaction(function () use ($event, $ticketType, $data, $user, $quantity): RegistrationCheckoutResult {
            try {
                $reservation = $this->ticketAvailability->reserve($ticketType, $quantity);
            } catch (\RuntimeException) {
                return $this->createWaitingListEntry($event, $ticketType, $data, $user);
            }

            $lineItems = [[
                'ticket_type_id' => $ticketType->id,
                'quantity' => $quantity,
                'unit_price' => (float) $ticketType->price,
            ]];

            $subtotal = round((float) $ticketType->price * $quantity, 2);
            $discountTotal = 0.0;
            $couponId = null;

            if (! empty($data['coupon_code'])) {
                $couponResult = $this->couponService->apply($event, (string) $data['coupon_code'], $lineItems);
                $subtotal = $couponResult['subtotal'];
                $discountTotal = $couponResult['discount_total'];
                $couponId = $couponResult['coupon']->id;
            }

            $total = max(0, round($subtotal - $discountTotal, 2));

            $primaryRegistration = Registration::query()->create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'user_id' => $user?->id,
                'ticket_type_id' => $ticketType->id,
                'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                'status' => RegistrationStatus::Pending,
                'rsvp_response' => isset($data['rsvp_response'])
                    ? \App\Enums\RsvpResponse::from((string) $data['rsvp_response'])
                    : null,
                'guest_invite_id' => $data['guest_invite_id'] ?? null,
                'is_plus_one' => (bool) ($data['is_plus_one'] ?? false),
                'primary_registration_id' => $data['primary_registration_id'] ?? null,
                'table_id' => $data['table_id'] ?? null,
                'attendee_first_name' => $data['attendee_first_name'],
                'attendee_last_name' => $data['attendee_last_name'],
                'attendee_email' => $data['attendee_email'],
                'attendee_phone' => $data['attendee_phone'] ?? null,
                'custom_fields' => $data['custom_fields'] ?? [],
            ]);

            $order = Order::query()->create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'user_id' => $user?->id,
                'registration_id' => $primaryRegistration->id,
                'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
                'status' => OrderStatus::PendingPayment,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => 0,
                'total' => $total,
                'currency' => $ticketType->currency,
                'coupon_id' => $couponId,
            ]);

            $primaryRegistration->update(['order_id' => $order->id]);

            $reservation->update(['order_id' => $order->id]);

            $order->items()->create([
                'organization_id' => $event->organization_id,
                'ticket_type_id' => $ticketType->id,
                'registration_id' => $primaryRegistration->id,
                'description' => $ticketType->name,
                'quantity' => $quantity,
                'unit_price' => $ticketType->price,
                'total_price' => round((float) $ticketType->price * $quantity, 2),
            ]);

            for ($i = 1; $i < $quantity; $i++) {
                Registration::query()->create([
                    'organization_id' => $event->organization_id,
                    'event_id' => $event->id,
                    'user_id' => $user?->id,
                    'ticket_type_id' => $ticketType->id,
                    'order_id' => $order->id,
                    'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                    'status' => RegistrationStatus::Pending,
                    'primary_registration_id' => $primaryRegistration->id,
                    'attendee_first_name' => $data['attendee_first_name'],
                    'attendee_last_name' => $data['attendee_last_name'],
                    'attendee_email' => $data['attendee_email'],
                    'attendee_phone' => $data['attendee_phone'] ?? null,
                    'custom_fields' => $data['custom_fields'] ?? [],
                ]);
            }

            if ($total <= 0) {
                $this->fulfillPaidOrderAction->handle($order);
                $order->refresh();
                $primaryRegistration->refresh();
            } else {
                $notifiable = new \Illuminate\Notifications\AnonymousNotifiable;
                $notifiable->route('mail', $primaryRegistration->attendee_email);
                $notifiable->notify(new RegistrationConfirmationNotification($primaryRegistration, $order));
            }

            return new RegistrationCheckoutResult(
                registration: $primaryRegistration,
                order: $order->fresh(['items']),
            );
        });
    }

    private function assertEventRegisterable(Event $event): void
    {
        if ($event->status !== EventStatus::Published) {
            throw ValidationException::withMessages([
                'event' => ['This event is not open for registration.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createWaitingListEntry(
        Event $event,
        TicketType $ticketType,
        array $data,
        ?User $user,
    ): RegistrationCheckoutResult {
        $entry = WaitingListEntry::query()->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'user_id' => $user?->id,
            'attendee_first_name' => $data['attendee_first_name'],
            'attendee_last_name' => $data['attendee_last_name'],
            'attendee_email' => $data['attendee_email'],
            'attendee_phone' => $data['attendee_phone'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? [],
            'status' => WaitingListStatus::Waiting,
        ]);

        return new RegistrationCheckoutResult(waitingListEntry: $entry);
    }
}
