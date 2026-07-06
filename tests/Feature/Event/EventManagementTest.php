<?php

declare(strict_types=1);

namespace Tests\Feature\Event;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EventManagementTest extends TestCase
{
    public function test_owner_can_create_publish_and_list_publicly(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson('/api/v1/events', [
                'name' => 'Innovation Summit',
                'timezone' => 'UTC',
                'visibility' => 'private',
                'venue' => 'Moscone Center',
                'starts_at' => now()->addMonth()->toIso8601String(),
                'ends_at' => now()->addMonth()->addDay()->toIso8601String(),
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $eventId = $create->json('data.id');

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$eventId}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->getJson('/api/v1/public/events?organization_id='.$organization->id)
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'Innovation Summit');
    }

    public function test_cannot_publish_without_required_fields(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Incomplete Event',
            'slug' => 'incomplete-event',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'starts_at' => null,
            'ends_at' => null,
            'venue' => null,
        ]);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/publish")
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['venue', 'starts_at']]);
    }

    public function test_duplicate_produces_independent_draft_copy(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Original Event',
            'slug' => 'original-event',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => 'public',
            'venue' => 'Convention Hall',
            'theme_config' => ['primary_color' => '#111111', 'secondary_color' => '#222222', 'font' => 'Inter', 'layout_variant' => 'classic'],
            'landing_page_config' => ['blocks' => [['type' => 'hero', 'settings' => (object) []]]],
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
            'published_at' => now(),
        ]);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.name', 'Original Event (Copy)');

        $copyId = $response->json('data.id');

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$copyId}/builder", [
                'theme_config' => [
                    'primary_color' => '#ABCDEF',
                    'secondary_color' => '#FEDCBA',
                    'font' => 'Inter',
                    'layout_variant' => 'classic',
                ],
                'landing_page_config' => ['blocks' => [['type' => 'cta', 'settings' => ['button_label' => 'Register now']]]],
            ])
            ->assertOk();

        $event->refresh();
        $this->assertSame('#111111', $event->theme_config['primary_color']);
    }

    public function test_user_from_org_a_cannot_update_org_b_event(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $orgA = app(CreateOrganizationAction::class)->handle($userA, 'Org A');
        $orgB = app(CreateOrganizationAction::class)->handle($userB, 'Org B');

        $eventB = Event::withoutTenantScope('seed')->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Event',
            'slug' => 'org-b-event',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
            'venue' => 'Hall B',
        ]);

        Sanctum::actingAs($userA);

        $this->withHeader('X-Organization-Id', (string) $orgB->id)
            ->putJson("/api/v1/events/{$eventB->id}", ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->withHeader('X-Organization-Id', (string) $orgA->id)
            ->putJson("/api/v1/events/{$eventB->id}", ['name' => 'Hijacked'])
            ->assertNotFound();
    }
}
