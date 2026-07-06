<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use App\Enums\EventCategory;
use App\Enums\EventVisibility;
use App\Rules\LandingPageConfigRule;
use App\Rules\ThemeConfigRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'visibility' => ['required', Rule::enum(EventVisibility::class)],
            'category' => ['nullable', Rule::enum(EventCategory::class)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'custom_domain' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'theme_config' => ['nullable', new ThemeConfigRule],
            'landing_page_config' => ['nullable', new LandingPageConfigRule],
        ];
    }
}
