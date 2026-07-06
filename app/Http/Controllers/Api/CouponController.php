<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Models\Event;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class CouponController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $coupons = $event->coupons()->orderBy('code')->get();

        return ApiResponse::success(CouponResource::collection($coupons));
    }

    public function store(StoreCouponRequest $request, Event $event): JsonResponse
    {
        $this->authorize('create', Coupon::class);
        $this->authorize('update', $event);

        $coupon = $event->coupons()->create([
            ...$request->validated(),
            'organization_id' => $event->organization_id,
            'code' => strtoupper(trim($request->validated('code'))),
        ]);

        return ApiResponse::created(
            new CouponResource($coupon),
            'Coupon created successfully.',
        );
    }

    public function update(
        UpdateCouponRequest $request,
        Event $event,
        Coupon $coupon,
    ): JsonResponse
    {
        $this->authorize('update', $coupon);
        abort_unless($coupon->event_id === $event->id, 404);

        $data = $request->validated();

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        $coupon->update($data);

        return ApiResponse::success(
            new CouponResource($coupon->fresh()),
            'Coupon updated successfully.',
        );
    }

    public function destroy(Event $event, Coupon $coupon): JsonResponse
    {
        $this->authorize('delete', $coupon);
        abort_unless($coupon->event_id === $event->id, 404);

        $coupon->delete();

        return ApiResponse::success(message: 'Coupon deleted successfully.');
    }
}
