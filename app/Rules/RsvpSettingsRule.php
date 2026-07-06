<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class RsvpSettingsRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('The :attribute must be an object.');

            return;
        }

        if (isset($value['allow_plus_ones']) && ! is_bool($value['allow_plus_ones'])) {
            $fail('The :attribute.allow_plus_ones must be a boolean.');
        }

        if (isset($value['max_plus_ones_per_invite'])) {
            if (! is_int($value['max_plus_ones_per_invite']) && ! ctype_digit((string) $value['max_plus_ones_per_invite'])) {
                $fail('The :attribute.max_plus_ones_per_invite must be an integer.');
            } elseif ((int) $value['max_plus_ones_per_invite'] < 0 || (int) $value['max_plus_ones_per_invite'] > 10) {
                $fail('The :attribute.max_plus_ones_per_invite must be between 0 and 10.');
            }
        }

        if (isset($value['collect_meal_preferences']) && ! is_bool($value['collect_meal_preferences'])) {
            $fail('The :attribute.collect_meal_preferences must be a boolean.');
        }

        if (isset($value['meal_options'])) {
            if (! is_array($value['meal_options'])) {
                $fail('The :attribute.meal_options must be an array.');
            } else {
                foreach ($value['meal_options'] as $option) {
                    if (! is_string($option) || mb_strlen($option) > 100) {
                        $fail('Each :attribute.meal_options entry must be a string (max 100 chars).');
                        break;
                    }
                }
            }
        }

        if (isset($value['allow_maybe_response']) && ! is_bool($value['allow_maybe_response'])) {
            $fail('The :attribute.allow_maybe_response must be a boolean.');
        }

        if (array_key_exists('response_deadline', $value) && $value['response_deadline'] !== null) {
            if (! is_string($value['response_deadline']) || strtotime($value['response_deadline']) === false) {
                $fail('The :attribute.response_deadline must be a valid datetime.');
            }
        }

        if (isset($value['auto_send_reminders']) && ! is_bool($value['auto_send_reminders'])) {
            $fail('The :attribute.auto_send_reminders must be a boolean.');
        }

        if (isset($value['reminder_days_before_deadline'])) {
            $days = $value['reminder_days_before_deadline'];
            if (! is_int($days) && ! ctype_digit((string) $days)) {
                $fail('The :attribute.reminder_days_before_deadline must be an integer.');
            } elseif ((int) $days < 1 || (int) $days > 30) {
                $fail('The :attribute.reminder_days_before_deadline must be between 1 and 30.');
            }
        }
    }
}
