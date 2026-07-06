<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organization;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
final class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $membership = $request->user()?->membershipFor($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $this->logo_path
                ? app(StorageService::class)->url($this->logo_path, 'public')
                : null,
            'owner_id' => $this->owner_id,
            'default_currency' => $this->resource->defaultCurrency(),
            'settings' => $this->settings ?? [],
            'role' => $membership?->role,
            'membership_status' => $membership?->status?->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
