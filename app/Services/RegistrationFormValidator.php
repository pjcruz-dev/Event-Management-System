<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RegistrationForm;
use Illuminate\Validation\Rule;

final class RegistrationFormValidator
{
    /**
     * @return array<string, mixed>
     */
    public function rulesFor(RegistrationForm $form): array
    {
        $rules = [
            'attendee_first_name' => ['required', 'string', 'max:255'],
            'attendee_last_name' => ['required', 'string', 'max:255'],
            'attendee_email' => ['required', 'email', 'max:255'],
            'attendee_phone' => ['nullable', 'string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
        ];

        foreach ($form->fields as $field) {
            if (! is_array($field) || ! isset($field['key'], $field['type'])) {
                continue;
            }

            $key = 'custom_fields.'.$field['key'];
            $fieldRules = $this->rulesForField($field);
            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<string|Rule>
     */
    private function rulesForField(array $field): array
    {
        $required = (bool) ($field['required'] ?? false);
        $base = [$required ? 'required' : 'nullable'];

        return match ($field['type']) {
            'email' => [...$base, 'email', 'max:255'],
            'phone' => [...$base, 'string', 'max:50'],
            'textarea' => [...$base, 'string', 'max:5000'],
            'checkbox' => [...$base, 'boolean'],
            'select' => [
                ...$base,
                'string',
                Rule::in($field['options'] ?? []),
            ],
            default => [...$base, 'string', 'max:255'],
        };
    }
}
