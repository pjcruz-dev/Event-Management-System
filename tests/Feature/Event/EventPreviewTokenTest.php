<?php

declare(strict_types=1);

namespace Tests\Feature\Event;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\EventPreviewToken;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EventPreviewTokenTest extends TestCase
{
    public function test_valid_token_returns_draft_event_preview_payload(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Preview Org');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Draft Gala',
            'slug' => 'draft-gala',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
            'venue' => 'Grand Hall',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $previewToken = EventPreviewToken::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addWeek(),
            'created_by' => $owner->id,
        ]);

        $this->getJson("/api/v1/public/events/{$event->slug}/preview?token={$previewToken->token}")
            ->assertOk()
            ->assertJsonPath('data.event.slug', 'draft-gala')
            ->assertJsonPath('data.preview', true);

        $previewToken->refresh();
        $this->assertNotNull($previewToken->last_used_at);
    }

    public function test_expired_or_revoked_or_wrong_slug_returns_not_found(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Preview Org');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Draft Gala',
            'slug' => 'draft-gala',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
            'venue' => 'Grand Hall',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $expired = EventPreviewToken::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->subDay(),
            'created_by' => $owner->id,
        ]);

        $this->getJson("/api/v1/public/events/{$event->slug}/preview?token={$expired->token}")
            ->assertNotFound();

        $revoked = EventPreviewToken::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addWeek(),
            'created_by' => $owner->id,
            'revoked_at' => now(),
        ]);

        $this->getJson("/api/v1/public/events/{$event->slug}/preview?token={$revoked->token}")
            ->assertNotFound();

        $valid = EventPreviewToken::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addWeek(),
            'created_by' => $owner->id,
        ]);

        $this->getJson("/api/v1/public/events/wrong-slug/preview?token={$valid->token}")
            ->assertNotFound();
    }

    public function test_org_b_cannot_create_preview_token_for_org_a_event(): void
    {
        $ownerA = User::factory()->create(['email_verified_at' => now()]);
        $ownerB = User::factory()->create(['email_verified_at' => now()]);
        $orgA = app(CreateOrganizationAction::class)->handle($ownerA, 'Org A');
        app(CreateOrganizationAction::class)->handle($ownerB, 'Org B');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A Event',
            'slug' => 'org-a-event',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
            'venue' => 'Hall',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        Sanctum::actingAs($ownerB);

        $this->withHeader('X-Organization-Id', (string) $orgA->id)
            ->postJson("/api/v1/events/{$event->id}/preview-token")
            ->assertForbidden();
    }

    public function test_published_public_event_still_available_without_token(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Live Org');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Live Summit',
            'slug' => 'live-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Convention Center',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
            'published_at' => now(),
        ]);

        $this->getJson("/api/v1/public/events/{$event->slug}")
            ->assertOk()
            ->assertJsonPath('data.event.slug', 'live-summit');
    }

    public function test_organizer_can_rotate_and_revoke_preview_token(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Preview Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Rotate Test',
            'slug' => 'rotate-test',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
            'venue' => 'Hall',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $create = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/preview-token")
            ->assertCreated();

        $firstToken = $create->json('data.token');

        $rotate = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/preview-token")
            ->assertCreated();

        $secondToken = $rotate->json('data.token');
        $this->assertNotSame($firstToken, $secondToken);

        $this->getJson("/api/v1/public/events/{$event->slug}/preview?token={$firstToken}")
            ->assertNotFound();

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->deleteJson("/api/v1/events/{$event->id}/preview-token")
            ->assertOk();

        $this->getJson("/api/v1/public/events/{$event->slug}/preview?token={$secondToken}")
            ->assertNotFound();
    }
}
