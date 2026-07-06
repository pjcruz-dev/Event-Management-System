<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Events\OrderPaid;
use App\Events\RegistrationConfirmed;
use App\Models\Order;
use App\Models\Registration;
use Illuminate\Validation\ValidationException;

final class FulfillPaidOrderAction extends BaseAction
{
    public function handle(mixed ...$args): Order
    {
        /** @var Order $order */
        $order = $args[0];

        if ($order->status === OrderStatus::Paid) {
            return $order;
        }

        if ($order->status !== OrderStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'order' => ['Only pending-payment orders can be marked as paid.'],
            ]);
        }

        // Atomic status transition — prevents duplicate fulfillment from concurrent webhooks.
        $affected = Order::query()
            ->where('id', $order->id)
            ->where('status', OrderStatus::PendingPayment)
            ->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
            ]);

        if ($affected === 0) {
            return $order->fresh(['registration', 'registrations', 'items']);
        }

        $registrations = Registration::query()
            ->where('order_id', $order->id)
            ->get();

        if ($registrations->isEmpty()) {
            $legacy = $order->registration;
            if ($legacy !== null) {
                $registrations = collect([$legacy]);
            }
        }

        foreach ($registrations as $registration) {
            $registration->update(['status' => RegistrationStatus::Confirmed]);
        }

        $freshOrder = $order->fresh(['registration', 'registrations']);
        event(new OrderPaid($freshOrder));

        foreach ($registrations as $registration) {
            event(new RegistrationConfirmed($registration->fresh()));
        }

        return $order->fresh(['registration', 'registrations', 'items']);
    }
}
