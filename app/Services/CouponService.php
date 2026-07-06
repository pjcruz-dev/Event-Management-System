<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\Event;
use Illuminate\Validation\ValidationException;

final class CouponService
{
    /**
     * @param  list<array{ticket_type_id: int, quantity: int, unit_price: float}>  $lineItems
     * @return array{coupon: Coupon, subtotal: float, discount_total: float, total: float}
     */
    public function apply(Event $event, string $code, array $lineItems): array
    {
        $coupon = Coupon::withoutTenantScope('public coupon validation')
            ->where('event_id', $event->id)
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->first();

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon code is not valid.'],
            ]);
        }

        $this->assertCouponUsable($coupon, $lineItems);

        $subtotal = round(array_reduce(
            $lineItems,
            fn (float $carry, array $item): float => $carry + ($item['unit_price'] * $item['quantity']),
            0.0,
        ), 2);

        $eligibleSubtotal = $this->eligibleSubtotal($coupon, $lineItems);
        $discountTotal = $this->calculateDiscount($coupon, $eligibleSubtotal);
        $total = max(0, round($subtotal - $discountTotal, 2));

        return [
            'coupon' => $coupon,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => $total,
        ];
    }

    /**
     * @param  list<array{ticket_type_id: int, quantity: int, unit_price: float}>  $lineItems
     */
    private function assertCouponUsable(Coupon $coupon, array $lineItems): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon is no longer active.'],
            ]);
        }

        if ($coupon->valid_from !== null && $coupon->valid_from->isFuture()) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon is not yet valid.'],
            ]);
        }

        if ($coupon->valid_until !== null && $coupon->valid_until->isPast()) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon has expired.'],
            ]);
        }

        if ($coupon->max_uses !== null && $coupon->times_used >= $coupon->max_uses) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon has reached its usage limit.'],
            ]);
        }

        $applicableIds = $coupon->applicable_ticket_type_ids;

        if ($applicableIds !== null && $applicableIds !== []) {
            $cartIds = array_column($lineItems, 'ticket_type_id');
            $overlap = array_intersect($applicableIds, $cartIds);

            if ($overlap === []) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['This coupon does not apply to the selected ticket types.'],
                ]);
            }
        }
    }

    /**
     * @param  list<array{ticket_type_id: int, quantity: int, unit_price: float}>  $lineItems
     */
    private function eligibleSubtotal(Coupon $coupon, array $lineItems): float
    {
        $applicableIds = $coupon->applicable_ticket_type_ids;

        $eligible = array_filter(
            $lineItems,
            fn (array $item): bool => $applicableIds === null
                || $applicableIds === []
                || in_array($item['ticket_type_id'], $applicableIds, true),
        );

        return round(array_reduce(
            $eligible,
            fn (float $carry, array $item): float => $carry + ($item['unit_price'] * $item['quantity']),
            0.0,
        ), 2);
    }

    private function calculateDiscount(Coupon $coupon, float $eligibleSubtotal): float
    {
        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        return match ($coupon->discount_type) {
            CouponDiscountType::Percentage => round(
                $eligibleSubtotal * ((float) $coupon->discount_value / 100),
                2,
            ),
            CouponDiscountType::Fixed => min((float) $coupon->discount_value, $eligibleSubtotal),
        };
    }
}
