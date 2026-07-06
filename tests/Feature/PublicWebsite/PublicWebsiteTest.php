<?php

declare(strict_types=1);

namespace Tests\Feature\PublicWebsite;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Review;
use App\Models\TicketType;
use App\Models\User;
use Tests\TestCase;

final class PublicWebsiteTest extends TestCase
{
    public function test_draft_events_are_excluded_from_discover(): void
    {
        $this->seedPublishedEvent('live-summit');
        $this->seedDraftEvent('hidden-draft');

        $this->getJson('/api/v1/discover/events')
            ->assertOk()
            ->assertJsonPath('success', true);

        $slugs = collect($this->getJson('/api/v1/discover/events')->json('data.items'))
            ->pluck('slug')
            ->all();

        $this->assertContains('live-summit', $slugs);
        $this->assertNotContains('hidden-draft', $slugs);
    }

    public function test_draft_event_slug_returns_not_found_on_public_show(): void
    {
        $this->seedDraftEvent('secret-draft');

        $this->getJson('/api/v1/public/events/secret-draft')->assertNotFound();
    }

    public function test_sitemap_only_includes_published_public_events(): void
    {
        $published = $this->seedPublishedEvent('public-showcase');
        $this->seedPrivatePublishedEvent('members-only');
        $this->seedDraftEvent('not-ready');

        $response = $this->getJson('/api/v1/discover/events/sitemap')->assertOk();
        $slugs = collect($response->json('data.events'))->pluck('slug')->all();

        $this->assertContains($published->slug, $slugs);
        $this->assertNotContains('members-only', $slugs);
        $this->assertNotContains('not-ready', $slugs);
    }

    public function test_discover_filters_by_category_and_keyword(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Filter Org');

        Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Design Leadership Summit',
            'slug' => 'design-leadership',
            'category' => EventCategory::Conference,
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Chicago',
            'timezone' => 'UTC',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addDay(),
        ]);

        Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Neighborhood Meetup',
            'slug' => 'neighborhood-meetup',
            'category' => EventCategory::Meetup,
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Denver',
            'timezone' => 'UTC',
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addHours(3),
        ]);

        $this->getJson('/api/v1/discover/events?category=conference&q=Design')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.slug', 'design-leadership');
    }

    public function test_attendee_can_submit_review_after_event_ends(): void
    {
        [$event, $registration] = $this->seedEndedEventWithRegistration();

        $this->postJson("/api/v1/public/events/{$event->slug}/reviews", [
            'registration_number' => $registration->registration_number,
            'attendee_email' => $registration->attendee_email,
            'rating' => 5,
            'comment' => 'Fantastic experience.',
        ])->assertCreated()
            ->assertJsonPath('data.rating', 5);

        $this->getJson("/api/v1/public/events/{$event->slug}/reviews")
            ->assertOk()
            ->assertJsonPath('data.items.0.comment', 'Fantastic experience.');
    }

    public function test_public_organization_profile_requires_opt_in(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Private Org');

        $this->getJson('/api/v1/public/organizations/'.$organization->slug)->assertNotFound();

        $organization->update([
            'settings' => [
                'public_profile' => [
                    'enabled' => true,
                    'description' => 'We run great events.',
                ],
            ],
        ]);

        $this->getJson('/api/v1/public/organizations/'.$organization->slug)
            ->assertOk()
            ->assertJsonPath('data.organization.name', 'Private Org');
    }

    public function test_public_speakers_and_sponsors_endpoints_return_conference_content(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Conference Org');
        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Speaker Showcase',
            'slug' => 'speaker-showcase-'.$organization->id,
            'category' => EventCategory::Conference,
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Austin',
            'timezone' => 'UTC',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        \App\Models\Speaker::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Dr. Ada Lovelace',
            'title' => 'Chief Scientist',
            'sort_order' => 0,
        ]);

        \App\Models\Sponsor::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Northwind Labs',
            'tier' => \App\Enums\SponsorTier::Gold,
            'website_url' => 'https://example.com',
            'sort_order' => 0,
        ]);

        $this->getJson("/api/v1/public/events/{$event->slug}/speakers")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Dr. Ada Lovelace');

        $this->getJson("/api/v1/public/events/{$event->slug}/sponsors")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Northwind Labs');
    }

    private function seedPublishedEvent(string $slug): Event
    {
        return $this->seedEvent($slug, EventStatus::Published, EventVisibility::Public);
    }

    private function seedDraftEvent(string $slug): Event
    {
        return $this->seedEvent($slug, EventStatus::Draft, EventVisibility::Public);
    }

    private function seedPrivatePublishedEvent(string $slug): Event
    {
        return $this->seedEvent($slug, EventStatus::Published, EventVisibility::Private);
    }

    private function seedEvent(string $slug, EventStatus $status, EventVisibility $visibility): Event
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org '.$slug);

        return Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'category' => EventCategory::Conference,
            'status' => $status,
            'visibility' => $visibility,
            'venue' => 'Test Venue',
            'timezone' => 'UTC',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);
    }

    /**
     * @return array{0: Event, 1: Registration}
     */
    private function seedEndedEventWithRegistration(): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Review Org');
        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Past Summit',
            'slug' => 'past-summit-'.$organization->id,
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Austin',
            'timezone' => 'UTC',
            'starts_at' => now()->subWeeks(2),
            'ends_at' => now()->subWeek(),
        ]);

        $ticketType = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 0,
            'currency' => 'USD',
            'quantity' => 100,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $registration = Registration::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'registration_number' => 'REG-REVIEW-1',
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => 'Jamie',
            'attendee_last_name' => 'Lee',
            'attendee_email' => 'jamie@example.com',
        ]);

        Order::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'order_number' => 'ORD-REVIEW-1',
            'status' => OrderStatus::Paid,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'currency' => 'USD',
            'paid_at' => now()->subWeeks(2),
        ]);

        return [$event, $registration];
    }
}
