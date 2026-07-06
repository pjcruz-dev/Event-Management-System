<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * @return array{checkout_url: string, session_id: string}
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{event_type: string, order_id: int|null, payment_intent_id: string|null, payment_method: string|null}
     */
    public function handleWebhook(string $payload, string $signature): array;

    /**
     * @return array{refund_id: string, status: string}
     */
    public function refund(Order $order, float $amount): array;
}
