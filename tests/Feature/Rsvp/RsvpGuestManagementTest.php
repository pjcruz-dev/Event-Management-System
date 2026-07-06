<?php

declare(strict_types=1);

namespace Tests\Feature\Rsvp;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventRegistrationMode;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\GuestInviteStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RsvpResponse;
use App\Enums\TicketTypeVisibility;
use App\Models\Event;
use App\Models\EventTable;
use App\Models\GuestInvite;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Services\GuestInviteAccessService;
use App\Services\GuestInviteService;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RsvpGuestManagementTest extends TestCase
{
    public function test_open_mode_public_register_unchanged(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org Open');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Open Summit',
            'slug' => 'open-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => EventRegistrationMode::Open,
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticket = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 0,
            'currency' => 'USD',
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/public/events/open-summit/register', [
            'ticket_type_id' => $ticket->id,
            'quantity' => 1,
            'attendee_first_name' => 'Alex',
            'attendee_last_name' => 'Rivera',
            'attendee_email' => 'alex@example.com',
        ])->assertCreated();
    }

    public function test_invite_only_register_without_token_returns_403(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org Invite');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Gala',
            'slug' => 'gala-night',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => EventRegistrationMode::InviteOnly,
        ]);

        $ticket = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Guest',
            'price' => 0,
            'currency' => 'USD',
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/public/events/gala-night/register', [
            'ticket_type_id' => $ticket->id,
            'quantity' => 1,
            'attendee_first_name' => 'Sam',
            'attendee_last_name' => 'Lee',
            'attendee_email' => 'sam@example.com',
        ])->assertStatus(403);
    }

    public function test_invite_only_register_with_valid_token_succeeds(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org Invite');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Gala',
            'slug' => 'gala-valid',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => EventRegistrationMode::InviteOnly,
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticket = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Guest',
            'price' => 0,
            'currency' => 'USD',
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $invite = GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'email' => 'guest@example.com',
            'first_name' => 'Guest',
            'last_name' => 'User',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Sent,
        ]);

        $this->postJson('/api/v1/public/events/gala-valid/register?invitation_token='.$invite->invitation_token, [
            'ticket_type_id' => $ticket->id,
            'quantity' => 1,
            'attendee_first_name' => 'Guest',
            'attendee_last_name' => 'User',
            'attendee_email' => 'guest@example.com',
        ])->assertCreated();
    }

    public function test_rsvp_accept_creates_confirmed_registration_with_qr(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org RSVP');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Wedding',
            'slug' => 'wedding',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => EventRegistrationMode::Rsvp,
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Guest',
            'price' => 0,
            'currency' => 'USD',
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $invite = GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'email' => 'bride-friend@example.com',
            'first_name' => 'Taylor',
            'last_name' => 'Guest',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Sent,
        ]);

        $response = $this->postJson('/api/v1/public/rsvp/'.$invite->invitation_token.'/respond', [
            'response' => 'accepted',
        ]);

        $response->assertCreated();

        $registration = Registration::withoutTenantScope('assert')->first();
        $this->assertNotNull($registration);
        $this->assertSame(RegistrationStatus::Confirmed, $registration->status);
        $this->assertNotNull($registration->qr_token_hash);
        $this->assertSame(RsvpResponse::Accepted, $registration->rsvp_response);
    }

    public function test_rsvp_decline_does_not_create_check_in_eligible_registration(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org RSVP');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Dinner',
            'slug' => 'dinner',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => EventRegistrationMode::Rsvp,
        ]);

        $invite = GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'email' => 'decline@example.com',
            'first_name' => 'No',
            'last_name' => 'Show',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Sent,
        ]);

        $this->postJson('/api/v1/public/rsvp/'.$invite->invitation_token.'/respond', [
            'response' => 'declined',
        ])->assertOk();

        $this->assertSame(0, Registration::withoutTenantScope('assert')->count());
        $invite->refresh();
        $this->assertSame(GuestInviteStatus::Declined, $invite->status);
    }

    public function test_plus_one_limit_enforced(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org Plus');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Party',
            'slug' => 'party',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => EventRegistrationMode::Rsvp,
            'rsvp_settings' => [
                'allow_plus_ones' => true,
                'max_plus_ones_per_invite' => 1,
            ],
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Guest',
            'price' => 0,
            'currency' => 'USD',
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $invite = GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'email' => 'host@example.com',
            'first_name' => 'Host',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Sent,
        ]);

        $this->postJson('/api/v1/public/rsvp/'.$invite->invitation_token.'/respond', [
            'response' => 'accepted',
            'plus_ones' => [
                ['first_name' => 'Plus', 'last_name' => 'One'],
                ['first_name' => 'Extra', 'last_name' => 'Guest'],
            ],
        ])->assertStatus(422);
    }

    public function test_revoked_token_returns_404_on_public_rsvp(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org Revoked');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Event',
            'slug' => 'revoked',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => EventRegistrationMode::Rsvp,
        ]);

        $invite = GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'email' => 'gone@example.com',
            'first_name' => 'Gone',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Revoked,
        ]);

        $this->getJson('/api/v1/public/rsvp/'.$invite->invitation_token)->assertNotFound();
    }

    public function test_seating_cannot_assign_beyond_capacity(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org Seat');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Dinner',
            'slug' => 'seat-dinner',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
        ]);

        $table = EventTable::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Table 1',
            'capacity' => 1,
        ]);

        $inviteA = GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'email' => 'a@example.com',
            'first_name' => 'A',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Pending,
        ]);

        $inviteB = GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'email' => 'b@example.com',
            'first_name' => 'B',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Pending,
        ]);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$event->id}/seating/assignments", [
                'guest_invites' => [
                    ['id' => $inviteA->id, 'table_id' => $table->id],
                    ['id' => $inviteB->id, 'table_id' => $table->id],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_guest_invite_csv_import_reports_errors(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org CSV');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Import Event',
            'slug' => 'import-event',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
            'registration_mode' => EventRegistrationMode::Rsvp,
        ]);

        $csv = "email,first_name,last_name\nvalid@example.com,Valid,Guest\ninvalid-email,Bad,Row\n";

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->post("/api/v1/events/{$event->id}/guest-invites/import", [
                'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('guests.csv', $csv),
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.created', 1);
        $response->assertJsonPath('data.skipped', 1);
        $this->assertCount(1, GuestInvite::withoutTenantScope('assert')->get());
    }

    public function test_tenant_isolation_for_guest_invites(): void
    {
        $ownerA = User::factory()->create(['email_verified_at' => now()]);
        $orgA = app(CreateOrganizationAction::class)->handle($ownerA, 'Org A');
        $ownerB = User::factory()->create(['email_verified_at' => now()]);
        $orgB = app(CreateOrganizationAction::class)->handle($ownerB, 'Org B');
        Sanctum::actingAs($ownerA);

        $eventA = Event::withoutTenantScope('seed')->create([
            'organization_id' => $orgA->id,
            'name' => 'Event A',
            'slug' => 'event-a',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
        ]);

        Event::withoutTenantScope('seed')->create([
            'organization_id' => $orgB->id,
            'name' => 'Event B',
            'slug' => 'event-b',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
        ]);

        GuestInvite::withoutTenantScope('seed')->create([
            'organization_id' => $orgB->id,
            'event_id' => Event::withoutTenantScope('seed')->where('organization_id', $orgB->id)->first()->id,
            'email' => 'secret@example.com',
            'first_name' => 'Secret',
            'invitation_token' => app(GuestInviteAccessService::class)->generateToken(),
            'status' => GuestInviteStatus::Pending,
        ]);

        $this->withHeader('X-Organization-Id', (string) $orgA->id)
            ->getJson("/api/v1/events/{$eventA->id}/guest-invites")
            ->assertOk()
            ->assertJsonPath('data.items', []);
    }
}
