<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Events\RegistrationCancelled;
use App\Models\Order;
use App\Models\Registration;
use App\Notifications\RefundProcessedNotification;
use App\Services\DashboardMetricsService;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RefundOrderAction extends BaseAction
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly DashboardMetricsService $metricsService,
    ) {}

    /**
     * @param  array{amount?: float|null, reason?: string|null}  $options
     */
    public function handle(mixed ...$args): Order
    {
        /** @var Order $order */
        $order = $args[0];
        /** @var array{amount?: float|null, reason?: string|null} $options */
        $options = $args[1] ?? [];

        if ($order->status !== OrderStatus::Paid) {
            throw ValidationException::withMessages([
                'order' => ['Only paid orders can be refunded.'],
            ]);
        }

        $refundAmount = $options['amount'] ?? (float) $order->total;
        $reason = $options['reason'] ?? null;
        $isFullRefund = $refundAmount >= (float) $order->total;

        return DB::transaction(function () use ($order, $refundAmount, $reason, $isFullRefund): Order {
            $stripeRefundId = null;

            if ($order->stripe_payment_intent_id !== null) {
                $result = $this->gateway->refund($order, $refundAmount);
                $stripeRefundId = $result['refund_id'] ?? null;
            }

            $order->update([
                'status' => $isFullRefund ? OrderStatus::Refunded : OrderStatus::Paid,
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
                'refund_reason' => $reason,
                'stripe_refund_id' => $stripeRefundId,
            ]);

            if ($isFullRefund) {
                $this->cancelRegistrations($order);
            }

            $order->loadMissing('event');
            $this->metricsService->invalidateForEvent($order->event);

            $this->notifyAttendee($order, $refundAmount, $isFullRefund);

            return $order->fresh(['registrations', 'items', 'event']);
        });
    }

    private function cancelRegistrations(Order $order): void
    {
        $registrations = Registration::query()
            ->where('order_id', $order->id)
            ->where('status', RegistrationStatus::Confirmed)
            ->get();

        foreach ($registrations as $registration) {
            $registration->update(['status' => RegistrationStatus::Cancelled]);
            event(new RegistrationCancelled($registration));
        }
    }

    private function notifyAttendee(Order $order, float $refundAmount, bool $isFullRefund): void
    {
        $registration = Registration::query()->where('order_id', $order->id)->first()
            ?? $order->registration;

        if ($registration === null) {
            return;
        }

        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $registration->attendee_email);
        $notifiable->notify(new RefundProcessedNotification(
            $order,
            $registration,
            $refundAmount,
            $isFullRefund,
        ));
    }
}
