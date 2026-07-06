<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserResource extends JsonResource
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
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'avatar_url' => $this->avatar_path
                ? app(StorageService::class)->url($this->avatar_path, 'public')
                : null,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
