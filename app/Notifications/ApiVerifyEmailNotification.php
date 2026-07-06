<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

final class ApiVerifyEmailNotification extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        $apiUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $frontend = rtrim((string) config('app.frontend_url'), '/');

        return $frontend.'/verify-email?'.http_build_query([
            'verification_url' => $apiUrl,
        ]);
    }
}
