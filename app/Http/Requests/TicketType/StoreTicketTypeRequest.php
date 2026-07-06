<?php

declare(strict_types=1);

namespace App\Http\Requests\TicketType;

use App\Enums\TicketTypeVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTicketTypeRequest extends FormRequest
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
        return $this->baseRules();
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type_tag' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', Rule::in([
                'USD', 'EUR', 'GBP', 'PHP', 'JPY', 'AUD', 'CAD', 'SGD',
                'INR', 'BRL', 'MXN', 'KRW', 'THB', 'MYR', 'IDR', 'VND',
                'CHF', 'SEK', 'NOK', 'DKK', 'NZD', 'HKD', 'TWD', 'ZAR',
            ])],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'per_order_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sales_starts_at' => ['nullable', 'date'],
            'sales_ends_at' => ['nullable', 'date', 'after:sales_starts_at'],
            'visibility' => ['required', Rule::enum(TicketTypeVisibility::class)],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
