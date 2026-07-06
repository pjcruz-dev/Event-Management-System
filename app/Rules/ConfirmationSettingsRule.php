<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ConfirmationSettingsRule implements ValidationRule
{
    private const ALLOWED_KEYS = [
        'rsvp_accepted_message',
        'rsvp_declined_message',
        'rsvp_maybe_message',
        'registration_pending_message',
        'registration_confirmed_message',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('The :attribute must be an object.');

            return;
        }

        foreach ($value as $key => $message) {
            if (! in_array($key, self::ALLOWED_KEYS, true)) {
                $fail("The :attribute contains an unknown key: {$key}.");

                return;
            }

            if ($message !== null && ! is_string($message)) {
                $fail("The :attribute.{$key} must be a string or null.");

                return;
            }

            if (is_string($message) && mb_strlen($message) > 2000) {
                $fail("The :attribute.{$key} must not exceed 2000 characters.");

                return;
            }
        }
    }
}
