<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class QrTokenService
{
    public function generate(Registration $registration): string
    {
        $payload = [
            'r' => $registration->id,
            'e' => $registration->event_id,
            'o' => $registration->organization_id,
            'n' => Str::random(16),
        ];

        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encoded);
        $token = $encoded.'.'.$signature;

        $registration->forceFill([
            'qr_token_hash' => hash('sha256', $token),
            'qr_token_encrypted' => Crypt::encryptString($token),
        ])->save();

        return $token;
    }

    public function resolveToken(Registration $registration): ?string
    {
        if ($registration->qr_token_encrypted === null) {
            return null;
        }

        try {
            return Crypt::decryptString($registration->qr_token_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function verify(string $token): ?Registration
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$encoded, $signature] = $parts;

        if (! hash_equals($this->sign($encoded), $signature)) {
            return null;
        }

        try {
            /** @var array{r: int, e: int, o: int, n: string} $payload */
            $payload = json_decode($this->base64UrlDecode($encoded), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! isset($payload['r'], $payload['e'], $payload['o'])) {
            return null;
        }

        $registration = Registration::withoutTenantScope('qr token verify')
            ->whereKey($payload['r'])
            ->where('event_id', $payload['e'])
            ->where('organization_id', $payload['o'])
            ->first();

        if ($registration === null) {
            return null;
        }

        if ($registration->qr_token_hash === null) {
            return null;
        }

        if (! hash_equals($registration->qr_token_hash, hash('sha256', $token))) {
            return null;
        }

        return $registration;
    }

    private function sign(string $encoded): string
    {
        return hash_hmac('sha256', $encoded, (string) config('checkin.signing_key'));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
