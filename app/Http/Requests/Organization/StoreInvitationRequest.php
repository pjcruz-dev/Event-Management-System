<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInvitationRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', 'string', 'max:64'],
        ];
    }
}
