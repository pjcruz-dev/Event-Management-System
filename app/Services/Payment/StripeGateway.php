<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Order;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook;

final class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {

        $lineItems = $order->items->map(fn ($item) => [
            'price_data' => [
                'currency' => strtolower($order->currency ?? 'usd'),
                'product_data' => [
                    'name' => $item->description,
                    'metadata' => [
                        'event_name' => $order->event->name,
                        'order_item_id' => $item->id,
                    ],
                ],
                'unit_amount' => (int) round((float) $item->unit_price * 100),
            ],
            'quantity' => $item->quantity,
        ])->toArray();

        $params = [
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'event_id' => $order->event_id,
                'organization_id' => $order->organization_id,
            ],
        ];

        if ((float) $order->discount_total > 0) {
            $discountCents = (int) round((float) $order->discount_total * 100);
            $stripeCoupon = \Stripe\Coupon::create([
                'amount_off' => $discountCents,
                'currency' => strtolower($order->currency ?? 'usd'),
                'duration' => 'once',
                'name' => 'Order discount',
            ]);
            $params['discounts'] = [['coupon' => $stripeCoupon->id]];
        }

        $email = $this->resolveCustomerEmail($order);
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $params['customer_email'] = $email;
        }

        $session = StripeSession::create($params);

        $order->update([
            'stripe_checkout_session_id' => $session->id,
            'payment_method' => 'stripe',
        ]);

        return [
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    public function handleWebhook(string $payload, string $signature): array
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        $event = Webhook::constructEvent($payload, $signature, $webhookSecret);

        return match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'checkout.session.expired' => $this->handleCheckoutExpired($event->data->object),
            default => ['event_type' => $event->type, 'order_id' => null, 'payment_intent_id' => null, 'payment_method' => null],
        };
    }

    private function handleCheckoutCompleted(object $session): array
    {
        $orderId = (int) ($session->metadata->order_id ?? 0);

        $order = Order::withoutTenantScope('stripe webhook')->find($orderId);
        if ($order !== null) {
            $order->update([
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
            ]);
        }

        return [
            'event_type' => 'payment_completed',
            'order_id' => $orderId,
            'payment_intent_id' => $session->payment_intent ?? null,
            'payment_method' => 'stripe',
        ];
    }

    private function handleCheckoutExpired(object $session): array
    {
        $orderId = (int) ($session->metadata->order_id ?? 0);

        return [
            'event_type' => 'payment_expired',
            'order_id' => $orderId,
            'payment_intent_id' => null,
            'payment_method' => 'stripe',
        ];
    }

    public function refund(Order $order, float $amount): array
    {
        if ($order->stripe_payment_intent_id === null) {
            throw new \RuntimeException('Cannot refund an order without a Stripe payment intent.');
        }

        $refund = \Stripe\Refund::create([
            'payment_intent' => $order->stripe_payment_intent_id,
            'amount' => (int) round($amount * 100),
        ]);

        return [
            'refund_id' => $refund->id,
            'status' => $refund->status,
        ];
    }

    private function resolveCustomerEmail(Order $order): ?string
    {
        $primary = $order->registrations()->first();
        if ($primary !== null) {
            return $primary->attendee_email;
        }

        $legacy = $order->registration;

        return $legacy?->attendee_email;
    }
}
