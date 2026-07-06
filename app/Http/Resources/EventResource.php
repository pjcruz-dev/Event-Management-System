<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
final class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $storage = app(StorageService::class);

        $themeConfig = $this->theme_config ?? [];
        $ogImagePath = $this->og_image_path;

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'venue' => $this->venue,
            'timezone' => $this->timezone,
            'capacity' => $this->capacity,
            'status' => $this->status?->value,
            'visibility' => $this->visibility?->value,
            'registration_mode' => $this->registration_mode?->value ?? 'open',
            'rsvp_settings' => $this->resolvedRsvpSettings(),
            'confirmation_settings' => $this->resolvedConfirmationSettings(),
            'category' => $this->category?->value,
            'theme_config' => $this->withAssetUrls($themeConfig, $storage),
            'landing_page_config' => $this->landing_page_config,
            'custom_domain' => $this->custom_domain,
            'custom_domain_verification_status' => $this->custom_domain_verification_status?->value,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'og_image_url' => $ogImagePath ? $storage->url($ogImagePath, 'public') : null,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $themeConfig
     * @return array<string, mixed>
     */
    private function withAssetUrls(array $themeConfig, StorageService $storage): array
    {
        foreach (['logo_url', 'hero_image_url', 'hero_video_url'] as $key) {
            if (isset($themeConfig[$key]) && is_string($themeConfig[$key]) && $themeConfig[$key] !== '') {
                $themeConfig[$key] = $storage->url($themeConfig[$key], 'public');
            }
        }

        return $themeConfig;
    }
}
