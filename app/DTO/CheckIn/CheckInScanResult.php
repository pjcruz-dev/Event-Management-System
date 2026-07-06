<?php

declare(strict_types=1);

namespace App\DTO\CheckIn;

use App\Models\Registration;

final readonly class CheckInScanResult
{
    /**
     * @param  array{checked_in_at: string, checked_in_by: string|null, gate: string|null}|null  $duplicateInfo
     */
    public function __construct(
        public string $status,
        public ?string $reason = null,
        public ?Registration $registration = null,
        public ?array $duplicateInfo = null,
        public bool $isVip = false,
    ) {}
}
