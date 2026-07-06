<?php

declare(strict_types=1);

namespace App\Services;

final class LandingPageHtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><strong><em><a><ul><ol><li><br>';

    public function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);
        $clean = preg_replace('/\s*on\w+\s*=\s*("|\').*?\1/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*javascript\s*:/i', '', $clean) ?? $clean;

        return trim($clean);
    }
}
