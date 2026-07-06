<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class OrganizationMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_path
                ? app(StorageService::class)->url($this->avatar_path, 'public')
                : null,
            'role' => $this->pivot?->role,
            'status' => $this->pivot?->status instanceof MembershipStatus
                ? $this->pivot->status->value
                : $this->pivot?->status,
            'joined_at' => $this->pivot?->created_at?->toIso8601String(),
        ];
    }
}
