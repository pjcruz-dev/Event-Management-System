<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use App\Enums\CouponDiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:50'],
            'discount_type' => ['sometimes', 'required', Rule::enum(CouponDiscountType::class)],
            'discount_value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'applicable_ticket_type_ids' => ['nullable', 'array'],
            'applicable_ticket_type_ids.*' => ['integer'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
        ];
    }
}
