<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TenantFixture;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TenantFixture */
final class TenantFixtureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'label' => $this->label,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
