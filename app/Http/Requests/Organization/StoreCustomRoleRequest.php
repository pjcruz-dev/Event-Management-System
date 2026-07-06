<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomRoleRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('roles', 'name')
                    ->where('organization_id', $organization?->id)
                    ->where('guard_name', 'web'),
            ],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(config('permissions', []))],
        ];
    }
}
