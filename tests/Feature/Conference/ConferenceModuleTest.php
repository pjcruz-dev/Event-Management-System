<?php

declare(strict_types=1);

namespace Tests\Feature\Conference;

use App\Actions\Order\FulfillPaidOrderAction;
use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Exhibitor;
use App\Models\ExhibitorContact;
use App\Models\ExhibitorLead;
use App\Models\Order;
use App\Models\Registration;
use App\Models\Speaker;
use App\Models\Track;
use App\Services\QrTokenService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ConferenceModuleTest extends TestCase
{
    public function test_session_capacity_is_enforced(): void
    {
        [$organization, $event, $owner, $track, $session, $registration] = $this->seedConferenceBasics(capacity: 1);
        Sanctum::actingAs($owner);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/public/events/{$event->slug}/sessions/{$session->id}/register", [
                'registration_number' => $registration->registration_number,
                'attendee_email' => $registration->attendee_email,
            ])
            ->assertCreated();

        $otherRegistration = $this->createPaidRegistration($organization, $event);

        $this->postJson("/api/v1/public/events/{$event->slug}/sessions/{$session->id}/register", [
            'registration_number' => $otherRegistration->registration_number,
            'attendee_email' => $otherRegistration->attendee_email,
        ])->assertUnprocessable();
    }

    public function test_session_store_returns_conflict_warnings(): void
    {
        [$organization, $event, $owner, $track] = $this->seedConferenceBasics();
        $speaker = Speaker::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Dr. Ada',
        ]);

        $starts = now()->addDay();
        EventSession::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'track_id' => $track->id,
            'title' => 'Room A Session',
            'room' => 'Hall 1',
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'is_published' => true,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/sessions", [
                'track_id' => $track->id,
                'title' => 'Overlapping Session',
                'room' => 'Hall 1',
                'starts_at' => $starts->copy()->addMinutes(30)->toIso8601String(),
                'ends_at' => $starts->copy()->addMinutes(90)->toIso8601String(),
                'speakers' => [['id' => $speaker->id, 'role' => 'speaker']],
                'is_published' => true,
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['warnings']]);
        $this->assertNotEmpty($response->json('data.warnings'));
    }

    public function test_exhibitor_contact_cannot_see_other_exhibitor_leads(): void
    {
        [$organization, $event, $owner] = $this->seedConferenceBasics();
        $registration = $this->createPaidRegistration($organization, $event);
        $token = app(QrTokenService::class)->generate($registration);

        $exhibitorA = Exhibitor::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Booth A Co',
        ]);
        $exhibitorB = Exhibitor::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Booth B Co',
        ]);

        $contactA = ExhibitorContact::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'exhibitor_id' => $exhibitorA->id,
            'name' => 'Contact A',
            'email' => 'a@exhibitor.test',
            'password' => Hash::make('password'),
        ]);
        ExhibitorContact::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'exhibitor_id' => $exhibitorB->id,
            'name' => 'Contact B',
            'email' => 'b@exhibitor.test',
            'password' => Hash::make('password'),
        ]);

        Sanctum::actingAs($contactA);

        $this->withHeader('X-Organization-Id', (string) $organization->id);

        $this->postJson('/api/v1/exhibitor-portal/leads/scan', ['token' => $token])
            ->assertCreated();

        $this->assertSame(1, ExhibitorLead::query()->where('exhibitor_id', $exhibitorA->id)->count());
        $this->assertSame(0, ExhibitorLead::query()->where('exhibitor_id', $exhibitorB->id)->count());

        $leads = $this->getJson('/api/v1/exhibitor-portal/leads')->assertOk();
        $this->assertCount(1, $leads->json('data'));
    }

    public function test_public_agenda_only_shows_published_sessions(): void
    {
        [$organization, $event, $owner, $track] = $this->seedConferenceBasics();
        $starts = now()->addDay();

        EventSession::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'track_id' => $track->id,
            'title' => 'Published Talk',
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'is_published' => true,
        ]);
        EventSession::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'track_id' => $track->id,
            'title' => 'Draft Talk',
            'starts_at' => $starts->copy()->addHours(2),
            'ends_at' => $starts->copy()->addHours(3),
            'is_published' => false,
        ]);

        $response = $this->getJson("/api/v1/public/events/{$event->slug}/agenda")->assertOk();
        $titles = collect($response->json('data.sessions'))->pluck('title');

        $this->assertTrue($titles->contains('Published Talk'));
        $this->assertFalse($titles->contains('Draft Talk'));
    }

    /**
     * @return array{0: \App\Models\Organization, 1: Event, 2: \App\Models\User, 3: Track, 4?: EventSession, 5?: Registration}
     */
    private function seedConferenceBasics(int $capacity = 50): array
    {
        $owner = \App\Models\User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Conf Org');
        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Conf Summit',
            'slug' => 'conf-summit-'.uniqid(),
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Convention Center',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addDays(2),
        ]);
        $track = Track::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Main Track',
        ]);
        $starts = now()->addDays(3);
        $session = EventSession::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'track_id' => $track->id,
            'title' => 'Workshop',
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'capacity' => $capacity,
            'is_published' => true,
        ]);
        $registration = $this->createPaidRegistration($organization, $event);

        return [$organization, $event, $owner, $track, $session, $registration];
    }

    private function createPaidRegistration(\App\Models\Organization $organization, Event $event): Registration
    {
        $registration = Registration::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'ticket_type_id' => \App\Models\TicketType::withoutTenantScope('seed')->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'name' => 'Pass',
                'price' => 0,
                'currency' => 'USD',
                'per_order_limit' => 1,
                'visibility' => 'public',
                'is_active' => true,
            ])->id,
            'registration_number' => 'REG-'.strtoupper(uniqid()),
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => 'Sam',
            'attendee_last_name' => 'Lee',
            'attendee_email' => uniqid().'@attendee.test',
        ]);

        $order = Order::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'order_number' => 'ORD-'.strtoupper(uniqid()),
            'status' => OrderStatus::Paid,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'currency' => 'USD',
            'paid_at' => now(),
        ]);

        $registration->update(['order_id' => $order->id]);

        app(FulfillPaidOrderAction::class)->handle($order);

        return $registration->fresh();
    }
}
