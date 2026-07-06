<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use App\Rules\LandingPageConfigRule;
use App\Rules\ThemeConfigRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEventBuilderRequest extends FormRequest
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
            'theme_config' => ['required', new ThemeConfigRule],
            'landing_page_config' => ['required', new LandingPageConfigRule],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
