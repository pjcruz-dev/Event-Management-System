<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiscoverEventResource;
use App\Models\Event;
use App\Models\Organization;
use App\Support\Responses\ApiResponse;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;

final class PublicOrganizationController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $organization = Organization::query()
            ->where('slug', $slug)
            ->first();

        if ($organization === null || ! $organization->hasPublicProfile()) {
            abort(404);
        }

        $profile = $organization->publicProfileSettings();
        $storage = app(StorageService::class);

        $events = Event::withoutTenantScope('public org events')
            ->where('organization_id', $organization->id)
            ->where('status', EventStatus::Published)
            ->where('visibility', EventVisibility::Public)
            ->with(['organization:id,name,slug'])
            ->withMin(['ticketTypes as min_price' => fn ($q) => $q
                ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('is_active', true)], 'price')
            ->withMax(['ticketTypes as max_price' => fn ($q) => $q
                ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('is_active', true)], 'price')
            ->orderBy('starts_at')
            ->get();

        return ApiResponse::success([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'logo_url' => $organization->logo_path
                    ? $storage->url($organization->logo_path, 'public')
                    : null,
                'description' => $profile['description'] ?? null,
                'website_url' => $profile['website_url'] ?? null,
            ],
            'events' => DiscoverEventResource::collection($events),
        ]);
    }
}
