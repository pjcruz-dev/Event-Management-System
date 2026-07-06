<?php

declare(strict_types=1);

namespace App\Http\Requests\Conference;

use App\Enums\SessionSpeakerRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateEventSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'track_id' => ['sometimes', 'integer', 'exists:tracks,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'room' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'speakers' => ['nullable', 'array'],
            'speakers.*.id' => ['required', 'integer', 'exists:speakers,id'],
            'speakers.*.role' => ['nullable', 'string', Rule::enum(SessionSpeakerRole::class)],
        ];
    }
}
