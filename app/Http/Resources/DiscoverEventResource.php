<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
final class DiscoverEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $storage = app(StorageService::class);
        $minPrice = $this->min_price !== null ? (float) $this->min_price : null;
        $maxPrice = $this->max_price !== null ? (float) $this->max_price : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'venue' => $this->venue,
            'category' => $this->category instanceof \App\Enums\EventCategory
                ? $this->category->value
                : $this->category,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'is_free' => $minPrice !== null && $minPrice <= 0,
            'currency' => $this->relationLoaded('ticketTypes')
                ? $this->ticketTypes->first()?->currency
                : null,
            'og_image_url' => $this->og_image_path
                ? $storage->url($this->og_image_path, 'public')
                : null,
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'slug' => $this->organization->slug,
            ]),
        ];
    }
}
