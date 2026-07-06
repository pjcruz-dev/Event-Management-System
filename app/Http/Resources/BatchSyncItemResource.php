<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTO\CheckIn\BatchSyncItemResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BatchSyncItemResult */
final class BatchSyncItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BatchSyncItemResult $result */
        $result = $this->resource;

        return [
            'idempotency_key' => $result->idempotencyKey,
            'status' => $result->status,
            'reason' => $result->reason,
            'duplicate_info' => $result->duplicateInfo,
        ];
    }
}
