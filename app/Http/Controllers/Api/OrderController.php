<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\FulfillPaidOrderAction;
use App\Actions\Order\RefundOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\RefundOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->where('event_id', $event->id)
            ->with(['registrations.ticketType', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search): void {
                $q->where('order_number', 'like', $search)
                    ->orWhereHas('registrations', function ($rq) use ($search): void {
                        $rq->where('attendee_email', 'like', $search)
                            ->orWhere('attendee_first_name', 'like', $search)
                            ->orWhere('attendee_last_name', 'like', $search);
                    });
            });
        }

        $orders = $query->orderByDesc('created_at')->paginate(
            min($request->integer('per_page', 25), 100),
        );

        return ApiResponse::success([
            'items' => OrderResource::collection($orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Event $event, Order $order): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $order);
        $this->authorize('view', $order);

        return ApiResponse::success(
            new OrderResource($order->load(['registrations.ticketType', 'items'])),
        );
    }

    public function markPaid(
        Event $event,
        Order $order,
        FulfillPaidOrderAction $fulfillPaidOrderAction,
    ): JsonResponse {
        $this->authorize('update', $event);
        $this->ensureBelongsToEvent($event, $order);

        $fulfilled = $fulfillPaidOrderAction->handle($order);

        return ApiResponse::success(
            new OrderResource($fulfilled->load(['items', 'registration'])),
            'Order marked as paid.',
        );
    }

    public function refund(
        RefundOrderRequest $request,
        Event $event,
        Order $order,
        RefundOrderAction $refundOrderAction,
    ): JsonResponse {
        $this->ensureBelongsToEvent($event, $order);
        $this->authorize('refund', $order);

        $validated = $request->validated();
        $refunded = $refundOrderAction->handle($order, [
            'amount' => isset($validated['amount']) ? (float) $validated['amount'] : null,
            'reason' => $validated['reason'] ?? null,
        ]);

        return ApiResponse::success(
            new OrderResource($refunded->load(['registrations.ticketType', 'items'])),
            'Refund processed successfully.',
        );
    }

    private function ensureBelongsToEvent(Event $event, Order $order): void
    {
        if ($order->event_id !== $event->id) {
            abort(404);
        }
    }
}
