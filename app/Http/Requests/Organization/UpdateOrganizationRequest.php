<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends FormRequest
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
        $organization = $this->route('organization');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('organizations', 'slug')->ignore($organization?->id),
            ],
            'settings' => ['sometimes', 'nullable', 'array'],
            'settings.default_currency' => ['sometimes', 'string', Rule::in([
                'USD', 'EUR', 'GBP', 'PHP', 'JPY', 'AUD', 'CAD', 'SGD',
                'INR', 'BRL', 'MXN', 'KRW', 'THB', 'MYR', 'IDR', 'VND',
                'CHF', 'SEK', 'NOK', 'DKK', 'NZD', 'HKD', 'TWD', 'ZAR',
            ])],
        ];
    }
}
