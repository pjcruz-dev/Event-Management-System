<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class LandingPageConfigRule implements ValidationRule
{
    private const ALLOWED_BLOCK_TYPES = [
        'hero',
        'about',
        'image',
        'agenda-preview',
        'speakers-preview',
        'sponsors',
        'exhibitors',
        'faq',
        'cta',
    ];

    private const ALLOWED_LAYOUTS = ['grid', 'list'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('The :attribute must be a valid landing page configuration object.');

            return;
        }

        if (! array_key_exists('blocks', $value)) {
            $fail('The :attribute must include a blocks array.');

            return;
        }

        if (! is_array($value['blocks'])) {
            $fail('The :attribute blocks must be an array.');

            return;
        }

        if ($value['blocks'] === []) {
            $fail('The :attribute must include at least one block.');

            return;
        }

        foreach ($value['blocks'] as $index => $block) {
            if (! $this->validateBlock($block, $index, $fail)) {
                return;
            }
        }

        if (array_key_exists('tickets', $value)) {
            if (! is_array($value['tickets'])) {
                $fail('The :attribute tickets must be an object.');

                return;
            }

            $this->validateTickets($value['tickets'], $fail);
        }
    }

    /**
     * @param  array<string, mixed>  $tickets
     */
    private function validateTickets(array $tickets, Closure $fail): void
    {
        if (isset($tickets['visible']) && ! is_bool($tickets['visible'])) {
            $fail('The :attribute tickets.visible must be a boolean.');

            return;
        }

        if (isset($tickets['title']) && ! is_string($tickets['title'])) {
            $fail('The :attribute tickets.title must be a string.');

            return;
        }

        if (
            isset($tickets['position'])
            && ! in_array($tickets['position'], ['after_blocks', 'hidden'], true)
        ) {
            $fail('The :attribute tickets.position is not supported.');
        }
    }

    private function validateBlock(mixed $block, int $index, Closure $fail): bool
    {
        if (! is_array($block)) {
            $fail("The :attribute block at index {$index} must be an object.");

            return false;
        }

        if (! isset($block['type']) || ! is_string($block['type'])) {
            $fail("The :attribute block at index {$index} must have a type.");

            return false;
        }

        if (! in_array($block['type'], self::ALLOWED_BLOCK_TYPES, true)) {
            $fail("The :attribute block at index {$index} has an unsupported type.");

            return false;
        }

        if (isset($block['visible']) && ! is_bool($block['visible'])) {
            $fail("The :attribute block at index {$index} visible must be a boolean.");

            return false;
        }

        if (! isset($block['settings']) || ! is_array($block['settings'])) {
            $fail("The :attribute block at index {$index} must have a settings object.");

            return false;
        }

        if (array_is_list($block['settings']) && $block['settings'] !== []) {
            $fail("The :attribute block at index {$index} must have a settings object.");

            return false;
        }

        $this->validateBlockSettings($block['type'], $block['settings'], $index, $fail);

        return true;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateBlockSettings(string $type, array $settings, int $index, Closure $fail): void
    {
        foreach ($settings as $key => $settingValue) {
            if (! is_string($key)) {
                $fail("The :attribute block at index {$index} has an invalid settings key.");

                return;
            }

            if (! is_string($settingValue) && ! is_bool($settingValue) && ! is_int($settingValue) && ! is_float($settingValue)) {
                $fail("The :attribute block at index {$index} setting {$key} must be a scalar value.");

                return;
            }
        }

        match ($type) {
            'hero' => $this->validateHeroSettings($settings, $index, $fail),
            'about' => null,
            'agenda-preview' => $this->validateAgendaSettings($settings, $index, $fail),
            'speakers-preview' => $this->validateSpeakersSettings($settings, $index, $fail),
            'sponsors' => $this->validateSponsorsSettings($settings, $index, $fail),
            'exhibitors' => $this->validateExhibitorsSettings($settings, $index, $fail),
            'faq' => $this->validateFaqSettings($settings, $index, $fail),
            'cta' => $this->validateCtaSettings($settings, $index, $fail),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateHeroSettings(array $settings, int $index, Closure $fail): void
    {
        $this->validateOptionalBool($settings, 'show_register_button', $index, $fail);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateAgendaSettings(array $settings, int $index, Closure $fail): void
    {
        $this->validateLimit($settings, 'limit', 1, 20, $index, $fail);
        $this->validateOptionalBool($settings, 'show_view_all_link', $index, $fail);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateSpeakersSettings(array $settings, int $index, Closure $fail): void
    {
        $this->validateLimit($settings, 'limit', 1, 24, $index, $fail);
        $this->validateLayout($settings, $index, $fail);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateSponsorsSettings(array $settings, int $index, Closure $fail): void
    {
        $this->validateOptionalBool($settings, 'group_by_tier', $index, $fail);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateExhibitorsSettings(array $settings, int $index, Closure $fail): void
    {
        $this->validateLimit($settings, 'limit', 1, 48, $index, $fail);
        $this->validateLayout($settings, $index, $fail);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateFaqSettings(array $settings, int $index, Closure $fail): void
    {
        if (isset($settings['title']) && ! is_string($settings['title'])) {
            $fail("The :attribute block at index {$index} title must be a string.");
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateCtaSettings(array $settings, int $index, Closure $fail): void
    {
        foreach (['headline', 'subheadline', 'button_label'] as $key) {
            if (isset($settings[$key]) && ! is_string($settings[$key])) {
                $fail("The :attribute block at index {$index} {$key} must be a string.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateLimit(array $settings, string $key, int $min, int $max, int $index, Closure $fail): void
    {
        if (! isset($settings[$key])) {
            return;
        }

        if (! is_int($settings[$key]) && ! (is_string($settings[$key]) && ctype_digit($settings[$key]))) {
            $fail("The :attribute block at index {$index} {$key} must be an integer.");

            return;
        }

        $limit = (int) $settings[$key];

        if ($limit < $min || $limit > $max) {
            $fail("The :attribute block at index {$index} {$key} must be between {$min} and {$max}.");
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateLayout(array $settings, int $index, Closure $fail): void
    {
        if (! isset($settings['layout'])) {
            return;
        }

        if (! is_string($settings['layout']) || ! in_array($settings['layout'], self::ALLOWED_LAYOUTS, true)) {
            $fail("The :attribute block at index {$index} layout must be grid or list.");
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateOptionalBool(array $settings, string $key, int $index, Closure $fail): void
    {
        if (isset($settings[$key]) && ! is_bool($settings[$key])) {
            $fail("The :attribute block at index {$index} {$key} must be a boolean.");
        }
    }
}
