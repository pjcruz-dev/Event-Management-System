<?php

declare(strict_types=1);

namespace App\Http\Requests\GuestInvite;

use Illuminate\Foundation\Http\FormRequest;

final class StoreGuestInviteRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'household_name' => ['nullable', 'string', 'max:255'],
            'group_label' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'plus_one_limit' => ['nullable', 'integer', 'min:0', 'max:10'],
            'table_id' => ['nullable', 'integer', 'exists:event_tables,id'],
        ];
    }
}
