<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ThemeConfigRule implements ValidationRule
{
    private const ALLOWED_LAYOUTS = ['classic', 'minimal', 'bold', 'modern', 'conference'];

    private const ALLOWED_FONTS = [
        'Inter', 'Roboto', 'Open Sans', 'Lato', 'Merriweather',
        'Poppins', 'Montserrat', 'Playfair Display', 'Source Sans 3',
        'Raleway', 'Nunito', 'DM Sans',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('The :attribute must be a valid theme configuration object.');

            return;
        }

        $allowedKeys = [
            'primary_color',
            'secondary_color',
            'font',
            'logo_url',
            'hero_image_url',
            'hero_video_url',
            'hero_background_type',
            'layout_variant',
        ];

        foreach (array_keys($value) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                $fail("The :attribute contains an unknown key: {$key}.");

                return;
            }
        }

        if (isset($value['primary_color']) && ! $this->isHexColor($value['primary_color'])) {
            $fail('The :attribute primary_color must be a valid hex color.');

            return;
        }

        if (isset($value['secondary_color']) && ! $this->isHexColor($value['secondary_color'])) {
            $fail('The :attribute secondary_color must be a valid hex color.');

            return;
        }

        if (isset($value['font']) && ! in_array($value['font'], self::ALLOWED_FONTS, true)) {
            $fail('The :attribute font is not supported.');

            return;
        }

        if (isset($value['layout_variant']) && ! in_array($value['layout_variant'], self::ALLOWED_LAYOUTS, true)) {
            $fail('The :attribute layout_variant is not supported.');

            return;
        }

        foreach (['logo_url', 'hero_image_url', 'hero_video_url'] as $urlKey) {
            if (isset($value[$urlKey]) && $value[$urlKey] !== null && ! is_string($value[$urlKey])) {
                $fail("The :attribute {$urlKey} must be a string or null.");
            }
        }

        if (isset($value['hero_background_type']) && ! in_array($value['hero_background_type'], ['image', 'video', 'color'], true)) {
            $fail('The :attribute hero_background_type must be image, video, or color.');
        }
    }

    private function isHexColor(mixed $color): bool
    {
        return is_string($color) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }
}
