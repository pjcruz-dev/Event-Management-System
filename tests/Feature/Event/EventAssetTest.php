<?php

declare(strict_types=1);

namespace Tests\Feature\Event;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EventAssetTest extends TestCase
{
    public function test_upload_and_delete_logo_clears_theme_config_and_storage(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Asset Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Asset Summit',
            'slug' => 'asset-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'venue' => 'Hall A',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
            'theme_config' => [
                'primary_color' => '#1E40AF',
                'secondary_color' => '#F59E0B',
                'font' => 'Inter',
                'layout_variant' => 'classic',
                'logo_url' => null,
                'hero_image_url' => null,
            ],
        ]);

        $upload = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->post("/api/v1/events/{$event->id}/assets", [
                'type' => 'logo',
                'file' => UploadedFile::fake()->image('logo.png', 200, 80),
            ], ['Accept' => 'application/json']);

        $upload->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.theme_config.logo_url', fn (mixed $value) => is_string($value) && $value !== '');

        $event->refresh();
        $storedPath = $event->theme_config['logo_url'];
        $this->assertIsString($storedPath);
        Storage::disk('public')->assertExists($storedPath);

        $delete = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->deleteJson("/api/v1/events/{$event->id}/assets/logo");

        $delete->assertOk()
            ->assertJsonPath('data.theme_config.logo_url', null);

        $event->refresh();
        $this->assertNull($event->theme_config['logo_url']);
        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_user_from_another_org_cannot_delete_event_asset(): void
    {
        Storage::fake('public');

        $ownerA = User::factory()->create(['email_verified_at' => now()]);
        $ownerB = User::factory()->create(['email_verified_at' => now()]);
        $orgA = app(CreateOrganizationAction::class)->handle($ownerA, 'Org A');
        $orgB = app(CreateOrganizationAction::class)->handle($ownerB, 'Org B');

        $storage = app(StorageService::class);
        $path = $storage->store(
            UploadedFile::fake()->image('logo.png'),
            'events/1/assets',
            'public',
        );

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $orgB->id,
            'name' => 'Protected Event',
            'slug' => 'protected-event',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'venue' => 'Hall B',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
            'theme_config' => [
                'primary_color' => '#1E40AF',
                'secondary_color' => '#F59E0B',
                'font' => 'Inter',
                'layout_variant' => 'classic',
                'logo_url' => $path,
                'hero_image_url' => null,
            ],
        ]);

        Sanctum::actingAs($ownerA);

        $this->withHeader('X-Organization-Id', (string) $orgA->id)
            ->deleteJson("/api/v1/events/{$event->id}/assets/logo")
            ->assertNotFound();
    }

    public function test_invalid_asset_type_returns_422(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Asset Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Type Test',
            'slug' => 'type-test',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'venue' => 'Hall C',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->deleteJson("/api/v1/events/{$event->id}/assets/og_image")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }
}
