<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sponsor */
final class SponsorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(StorageService::class);

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path
                ? $storage->url($this->logo_path, 'public')
                : null,
            'website_url' => $this->website_url,
            'tier' => $this->tier->value,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
        ];
    }
}
