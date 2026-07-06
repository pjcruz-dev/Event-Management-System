<?php

declare(strict_types=1);

namespace App\Http\Requests\CheckIn;

use Illuminate\Foundation\Http\FormRequest;

final class SyncBatchCheckInRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.token' => ['required', 'string', 'max:2048'],
            'items.*.idempotency_key' => ['required', 'string', 'max:255'],
            'items.*.scanned_at' => ['required', 'date'],
            'items.*.device_id' => ['nullable', 'string', 'max:255'],
            'items.*.gate' => ['nullable', 'string', 'max:255'],
            'items.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'items.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
