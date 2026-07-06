<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\FulfillPaidOrderAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Organization;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\TenantContext;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    /**
     * Public endpoint: create a Stripe Checkout session for a pending-payment order.
     * Called by the frontend immediately after registration.
     */
    public function publicCheckout(
        Request $request,
        string $orderNumber,
        PaymentGatewayInterface $gateway,
        TenantContext $tenantContext,
    ): JsonResponse {
        $order = Order::withoutTenantScope('public checkout')
            ->where('order_number', $orderNumber)
            ->first();

        if ($order === null) {
            abort(404, 'Order not found.');
        }

        if (! $tenantContext->isResolved()) {
            $organization = Organization::query()->find($order->organization_id);
            if ($organization !== null) {
                $tenantContext->set($organization);
            }
        }

        if ($order->status !== OrderStatus::PendingPayment) {
            return ApiResponse::error('Order is not awaiting payment.', 422);
        }

        if ((float) $order->total <= 0) {
            return ApiResponse::error('Free orders do not require checkout.', 422);
        }

        $order->loadMissing(['event', 'items', 'registrations']);

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $slug = $order->event->slug;

        $successUrl = $frontendUrl.'/e/'.$slug.'/register/success?order='.$order->order_number;
        $cancelUrl = $frontendUrl.'/e/'.$slug.'/register/cancelled?order='.$order->order_number;

        $result = $gateway->createCheckoutSession($order, $successUrl, $cancelUrl);

        return ApiResponse::success([
            'checkout_url' => $result['checkout_url'],
            'session_id' => $result['session_id'],
        ], 'Checkout session created.');
    }

    /**
     * Stripe webhook handler. Verifies signature and fulfills paid orders.
     */
    public function handleWebhook(
        Request $request,
        PaymentGatewayInterface $gateway,
        FulfillPaidOrderAction $fulfillAction,
        TenantContext $tenantContext,
    ): JsonResponse {
        $signature = $request->header('Stripe-Signature', '');

        try {
            $result = $gateway->handleWebhook($request->getContent(), $signature);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        if ($result['order_id']) {
            $order = Order::withoutTenantScope('stripe webhook')
                ->find($result['order_id']);

            if ($order !== null && ! $tenantContext->isResolved()) {
                $organization = Organization::query()->find($order->organization_id);
                if ($organization !== null) {
                    $tenantContext->set($organization);
                }
            }

            if ($result['event_type'] === 'payment_completed' && $order !== null && $order->status === OrderStatus::PendingPayment) {
                $fulfillAction->handle($order);
                Log::info('Stripe payment fulfilled.', ['order_id' => $order->id]);
            }

            if ($result['event_type'] === 'payment_expired' && $order !== null && $order->status === OrderStatus::PendingPayment) {
                $order->update(['status' => OrderStatus::Failed]);
                Log::info('Stripe checkout expired.', ['order_id' => $order->id]);
            }
        }

        return response()->json(['received' => true]);
    }
}
