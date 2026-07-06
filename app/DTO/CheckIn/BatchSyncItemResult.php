<?php

declare(strict_types=1);

namespace App\DTO\CheckIn;

final readonly class BatchSyncItemResult
{
    /**
     * @param  array<string, mixed>|null  $duplicateInfo
     */
    public function __construct(
        public string $idempotencyKey,
        public string $status,
        public ?string $reason = null,
        public ?array $duplicateInfo = null,
    ) {}
}
