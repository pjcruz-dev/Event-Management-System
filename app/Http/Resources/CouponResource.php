<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Coupon */
final class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'code' => $this->code,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => (float) $this->discount_value,
            'applicable_ticket_type_ids' => $this->applicable_ticket_type_ids,
            'max_uses' => $this->max_uses,
            'times_used' => $this->times_used,
            'valid_from' => $this->valid_from?->toIso8601String(),
            'valid_until' => $this->valid_until?->toIso8601String(),
            'is_active' => $this->is_active,
        ];
    }
}
