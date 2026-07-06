<?php

declare(strict_types=1);

namespace App\Http\Requests\GuestInvite;

use App\Enums\RsvpResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublicRsvpRespondRequest extends FormRequest
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
            'response' => ['required', Rule::enum(RsvpResponse::class)],
            'attendee_first_name' => ['nullable', 'string', 'max:255'],
            'attendee_last_name' => ['nullable', 'string', 'max:255'],
            'attendee_email' => ['nullable', 'email', 'max:255'],
            'attendee_phone' => ['nullable', 'string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.meal_preference' => ['nullable', 'string', 'max:255'],
            'ticket_type_id' => ['nullable', 'integer'],
            'plus_ones' => ['nullable', 'array', 'max:10'],
            'plus_ones.*.first_name' => ['required_with:plus_ones', 'string', 'max:255'],
            'plus_ones.*.last_name' => ['nullable', 'string', 'max:255'],
            'plus_ones.*.email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
