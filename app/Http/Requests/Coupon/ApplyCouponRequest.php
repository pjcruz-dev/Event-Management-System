<?php

declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyCouponRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
            'ticket_type_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
