<?php

declare(strict_types=1);

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRegistrationFormRequest extends FormRequest
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
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'fields.*.type' => ['required', Rule::in(['text', 'email', 'phone', 'select', 'checkbox', 'textarea'])],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*' => ['string', 'max:255'],
        ];
    }
}
