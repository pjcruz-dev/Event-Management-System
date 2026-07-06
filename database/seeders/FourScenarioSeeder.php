<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventCategory;
use App\Enums\EventRegistrationMode;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\GuestInviteStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MembershipStatus;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RsvpResponse;
use App\Enums\TicketTypeVisibility;
use App\Enums\WaitingListStatus;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Services\OrganizationRoleService;
use App\Services\QrTokenService;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds exactly 4 events under one organization for manual QA testing:
 *
 *  1. Open + Paid  — paid tickets, 6 confirmed registrations with QR, 2 pending
 *  2. Open + Free  — free tickets, 5 auto-confirmed registrations with QR
 *  3. RSVP Gala    — 8 guest invites (4 accepted w/ QR, 2 declined, 2 pending)
 *  4. Walk-in Day  — published event, no pre-registrations, ready for on-site walk-ins
 *
 * Login: demo@event-saas.test / password
 */
final class FourScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $createOrg = app(CreateOrganizationAction::class);
        $roleService = app(OrganizationRoleService::class);
        $tenantContext = app(TenantContext::class);
        $qrService = app(QrTokenService::class);

        $owner = User::factory()->create([
            'name' => 'Demo Organizer',
            'email' => 'demo@event-saas.test',
            'email_verified_at' => now(),
        ]);

        $org = $createOrg->handle($owner, 'Summit Events Co');
        $org->update([
            'settings' => [
                'public_profile' => [
                    'enabled' => true,
                    'description' => 'Test organization for QR-unification QA.',
                    'website_url' => 'https://example.com',
                ],
            ],
        ]);

        $tenantContext->set($org);

        $staff = User::factory()->create([
            'name' => 'Staff Member',
            'email' => 'staff@event-saas.test',
            'email_verified_at' => now(),
        ]);
        $org->members()->attach($staff->id, [
            'role' => 'admin',
            'status' => MembershipStatus::Active->value,
            'invited_by' => $owner->id,
        ]);
        $roleService->assignRole($staff, $org, 'admin');

        // ─── Event 1: Open + Paid tickets ───────────────────────────
        $paidEvent = $this->createEvent($org, [
            'name' => 'Tech Conference 2026',
            'slug' => 'tech-conference-2026',
            'description' => 'A flagship paid conference with Standard and VIP tickets.',
            'venue' => 'Moscone Center, San Francisco, CA',
            'category' => EventCategory::Conference,
            'registration_mode' => EventRegistrationMode::Open,
            'capacity' => 500,
            'months_ahead' => 2,
            'duration_days' => 2,
            'primary_color' => '#1E40AF',
            'secondary_color' => '#F59E0B',
        ]);

        $standardTicket = $this->createTicket($org, $paidEvent, 'Standard Pass', 99.00, 300, 0);
        $vipTicket = $this->createTicket($org, $paidEvent, 'VIP Pass', 299.00, 50, 1, 'VIP');

        $this->seedPaidRegistrations($org, $paidEvent, $standardTicket, 4, $qrService);
        $this->seedPaidRegistrations($org, $paidEvent, $vipTicket, 2, $qrService);
        $this->seedMultiTicketPurchase($org, $paidEvent, $standardTicket, 3, $qrService);
        $this->seedPendingRegistrations($org, $paidEvent, $standardTicket, 2);

        // ─── Event 2: Open + Free tickets ───────────────────────────
        $freeEvent = $this->createEvent($org, [
            'name' => 'Community Meetup',
            'slug' => 'community-meetup',
            'description' => 'Free community meetup — register and get your QR instantly.',
            'venue' => 'GitHub HQ, San Francisco, CA',
            'category' => EventCategory::Meetup,
            'registration_mode' => EventRegistrationMode::Open,
            'capacity' => 120,
            'months_ahead' => 1,
            'duration_days' => 1,
            'primary_color' => '#059669',
            'secondary_color' => '#34D399',
        ]);

        $freeTicket = $this->createTicket($org, $freeEvent, 'Free Admission', 0.00, 120, 0);
        $this->seedFreeRegistrations($org, $freeEvent, $freeTicket, 5, $qrService);

        // ─── Event 3: RSVP Gala ─────────────────────────────────────
        $rsvpEvent = $this->createEvent($org, [
            'name' => 'Annual Charity Gala',
            'slug' => 'charity-gala-2026',
            'description' => 'Black-tie RSVP gala — guests receive invitations and respond.',
            'venue' => 'The Grand Ballroom, Chicago, IL',
            'category' => EventCategory::Festival,
            'registration_mode' => EventRegistrationMode::Rsvp,
            'rsvp_settings' => [
                'allow_plus_ones' => true,
                'max_plus_ones_per_invite' => 1,
                'collect_meal_preferences' => true,
                'allow_maybe_response' => true,
                'response_deadline' => null,
            ],
            'capacity' => 200,
            'months_ahead' => 3,
            'duration_days' => 1,
            'primary_color' => '#7C3AED',
            'secondary_color' => '#DB2777',
        ]);

        $galaTicket = $this->createTicket($org, $rsvpEvent, 'Guest Admission', 0.00, 200, 0);
        $this->seedGuestInvites($org, $rsvpEvent, $galaTicket, $qrService);

        // ─── Event 4: Walk-in Ready ─────────────────────────────────
        $walkinEvent = $this->createEvent($org, [
            'name' => 'Open House & Walk-in Day',
            'slug' => 'walkin-day-2026',
            'description' => 'A drop-in event — no pre-registration needed. Walk-ins checked in at the door.',
            'venue' => 'Convention Center Lobby, Austin, TX',
            'category' => EventCategory::Meetup,
            'registration_mode' => EventRegistrationMode::Open,
            'capacity' => 300,
            'months_ahead' => 1,
            'duration_days' => 1,
            'primary_color' => '#DC2626',
            'secondary_color' => '#FBBF24',
        ]);

        $walkinTicket = $this->createTicket($org, $walkinEvent, 'Door Entry', 0.00, 300, 0);

        // Also add 2 waitlisted entries for promotion testing
        $this->seedWaitlistEntries($org, $walkinEvent, $walkinTicket);

        $tenantContext->clear();

        $this->command->info('');
        $this->command->info('  4 scenarios seeded successfully!');
        $this->command->info('');
        $this->command->info('  Login: demo@event-saas.test / password');
        $this->command->info('');
        $this->command->table(
            ['#', 'Event', 'Scenario', 'What to test'],
            [
                ['1', 'Tech Conference 2026', 'Open + Paid', '6+3 confirmed (QR), 2 pending. 3 are multi-ticket.'],
                ['2', 'Community Meetup', 'Open + Free', '5 confirmed (QR). Free auto-confirmed registrations.'],
                ['3', 'Annual Charity Gala', 'RSVP', '4 accepted (QR), 2 declined, 2 pending. Guest invites.'],
                ['4', 'Open House & Walk-in Day', 'Walk-in', 'No registrations. Use Walk-in tab to register at door.'],
            ],
        );
        $this->command->info('');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createEvent(Organization $org, array $config): Event
    {
        $startsAt = now()->addMonths($config['months_ahead']);

        return Event::query()->create([
            'organization_id' => $org->id,
            'name' => $config['name'],
            'slug' => $config['slug'],
            'description' => $config['description'],
            'venue' => $config['venue'],
            'timezone' => 'America/Chicago',
            'capacity' => $config['capacity'],
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'registration_mode' => $config['registration_mode'],
            'rsvp_settings' => $config['rsvp_settings'] ?? null,
            'category' => $config['category'],
            'theme_config' => [
                'primary_color' => $config['primary_color'],
                'secondary_color' => $config['secondary_color'],
                'font' => 'Inter',
                'logo_url' => null,
                'hero_image_url' => null,
                'layout_variant' => 'classic',
            ],
            'landing_page_config' => [
                'blocks' => [
                    ['type' => 'hero', 'settings' => (object) [
                        'headline' => $config['name'],
                        'subheadline' => $config['description'],
                    ]],
                    ['type' => 'about', 'settings' => (object) ['body' => $config['description']]],
                    ['type' => 'cta', 'settings' => (object) ['button_label' => 'Register now']],
                ],
            ],
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays($config['duration_days']),
            'published_at' => now()->subDays(7),
        ]);
    }

    private function createTicket(
        Organization $org,
        Event $event,
        string $name,
        float $price,
        int $quantity,
        int $sortOrder,
        ?string $typeTag = null,
    ): TicketType {
        return TicketType::query()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'name' => $name,
            'description' => $price > 0 ? "Full access — \${$price}" : 'Complimentary entry.',
            'price' => $price,
            'currency' => 'USD',
            'quantity' => $quantity,
            'type_tag' => $typeTag,
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    private function seedPaidRegistrations(
        Organization $org,
        Event $event,
        TicketType $ticket,
        int $count,
        QrTokenService $qrService,
    ): void {
        for ($i = 0; $i < $count; $i++) {
            $price = (float) $ticket->price;
            $tax = round($price * 0.08, 2);

            $registration = Registration::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'ticket_type_id' => $ticket->id,
                'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                'status' => RegistrationStatus::Confirmed,
                'attendee_first_name' => fake()->firstName(),
                'attendee_last_name' => fake()->lastName(),
                'attendee_email' => fake()->unique()->safeEmail(),
                'attendee_phone' => fake()->phoneNumber(),
                'custom_fields' => ['company' => fake()->company()],
            ]);

            $order = \App\Models\Order::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
                'status' => OrderStatus::Paid,
                'subtotal' => $price,
                'discount_total' => 0,
                'tax_total' => $tax,
                'total' => round($price + $tax, 2),
                'currency' => 'USD',
                'paid_at' => now()->subDays(fake()->numberBetween(1, 10)),
            ]);

            $registration->update(['order_id' => $order->id]);

            $order->items()->create([
                'organization_id' => $org->id,
                'ticket_type_id' => $ticket->id,
                'registration_id' => $registration->id,
                'description' => $ticket->name,
                'quantity' => 1,
                'unit_price' => $price,
                'total_price' => $price,
            ]);

            if ($price > 0) {
                $order->invoice()->create([
                    'organization_id' => $org->id,
                    'invoice_number' => 'INV-'.strtoupper((string) Str::ulid()),
                    'status' => InvoiceStatus::Paid,
                    'subtotal' => $price,
                    'tax_total' => $tax,
                    'total' => round($price + $tax, 2),
                    'currency' => 'USD',
                    'issued_at' => $order->paid_at,
                    'due_at' => $order->paid_at?->copy()->addDays(14),
                    'paid_at' => $order->paid_at,
                ]);
            }

            $qrService->generate($registration);
            $ticket->increment('quantity_sold');
        }
    }

    private function seedMultiTicketPurchase(
        Organization $org,
        Event $event,
        TicketType $ticket,
        int $quantity,
        QrTokenService $qrService,
    ): void {
        $price = (float) $ticket->price;
        $subtotal = round($price * $quantity, 2);
        $tax = round($subtotal * 0.08, 2);

        $buyerFirst = 'Maria';
        $buyerLast = 'Garcia';
        $buyerEmail = 'maria.garcia@example.com';

        $primaryRegistration = Registration::query()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticket->id,
            'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => $buyerFirst,
            'attendee_last_name' => $buyerLast,
            'attendee_email' => $buyerEmail,
            'attendee_phone' => '555-0100',
            'custom_fields' => ['company' => 'Garcia Corp', 'note' => 'Multi-ticket buyer'],
        ]);

        $order = \App\Models\Order::query()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'registration_id' => $primaryRegistration->id,
            'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
            'status' => OrderStatus::Paid,
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => $tax,
            'total' => round($subtotal + $tax, 2),
            'currency' => 'USD',
            'paid_at' => now()->subDays(3),
        ]);

        $primaryRegistration->update(['order_id' => $order->id]);

        $order->items()->create([
            'organization_id' => $org->id,
            'ticket_type_id' => $ticket->id,
            'registration_id' => $primaryRegistration->id,
            'description' => $ticket->name.' x'.$quantity,
            'quantity' => $quantity,
            'unit_price' => $price,
            'total_price' => $subtotal,
        ]);

        $qrService->generate($primaryRegistration);
        $ticket->increment('quantity_sold');

        $additionalNames = [
            ['first' => 'Carlos', 'last' => 'Garcia'],
            ['first' => 'Ana', 'last' => 'Garcia'],
        ];

        for ($i = 0; $i < $quantity - 1; $i++) {
            $name = $additionalNames[$i] ?? ['first' => fake()->firstName(), 'last' => fake()->lastName()];

            $reg = Registration::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'ticket_type_id' => $ticket->id,
                'order_id' => $order->id,
                'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                'status' => RegistrationStatus::Confirmed,
                'primary_registration_id' => $primaryRegistration->id,
                'attendee_first_name' => $name['first'],
                'attendee_last_name' => $name['last'],
                'attendee_email' => strtolower($name['first'].'.'.$name['last'].'@example.com'),
                'custom_fields' => ['note' => 'Part of multi-ticket order'],
            ]);

            $qrService->generate($reg);
            $ticket->increment('quantity_sold');
        }
    }

    private function seedPendingRegistrations(
        Organization $org,
        Event $event,
        TicketType $ticket,
        int $count,
    ): void {
        $price = (float) $ticket->price;
        $tax = round($price * 0.08, 2);

        for ($i = 0; $i < $count; $i++) {
            $registration = Registration::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'ticket_type_id' => $ticket->id,
                'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                'status' => RegistrationStatus::Pending,
                'attendee_first_name' => fake()->firstName(),
                'attendee_last_name' => fake()->lastName(),
                'attendee_email' => fake()->unique()->safeEmail(),
                'custom_fields' => [],
            ]);

            $order = \App\Models\Order::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
                'status' => OrderStatus::PendingPayment,
                'subtotal' => $price,
                'discount_total' => 0,
                'tax_total' => $tax,
                'total' => round($price + $tax, 2),
                'currency' => 'USD',
            ]);

            $registration->update(['order_id' => $order->id]);
        }
    }

    private function seedFreeRegistrations(
        Organization $org,
        Event $event,
        TicketType $ticket,
        int $count,
        QrTokenService $qrService,
    ): void {
        for ($i = 0; $i < $count; $i++) {
            $registration = Registration::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'ticket_type_id' => $ticket->id,
                'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                'status' => RegistrationStatus::Confirmed,
                'attendee_first_name' => fake()->firstName(),
                'attendee_last_name' => fake()->lastName(),
                'attendee_email' => fake()->unique()->safeEmail(),
                'custom_fields' => [],
            ]);

            $order = \App\Models\Order::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
                'status' => OrderStatus::Paid,
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'currency' => 'USD',
                'paid_at' => now()->subDays(fake()->numberBetween(1, 7)),
            ]);

            $registration->update(['order_id' => $order->id]);

            $order->items()->create([
                'organization_id' => $org->id,
                'ticket_type_id' => $ticket->id,
                'registration_id' => $registration->id,
                'description' => $ticket->name,
                'quantity' => 1,
                'unit_price' => 0,
                'total_price' => 0,
            ]);

            $qrService->generate($registration);
            $ticket->increment('quantity_sold');
        }
    }

    private function seedGuestInvites(
        Organization $org,
        Event $event,
        TicketType $ticket,
        QrTokenService $qrService,
    ): void {
        $guests = [
            ['first' => 'Sarah',   'last' => 'Chen',    'status' => 'accepted'],
            ['first' => 'David',   'last' => 'Park',    'status' => 'accepted'],
            ['first' => 'Emily',   'last' => 'Brooks',  'status' => 'accepted'],
            ['first' => 'Robert',  'last' => 'Singh',   'status' => 'accepted'],
            ['first' => 'Michael', 'last' => 'Torres',  'status' => 'declined'],
            ['first' => 'Rachel',  'last' => 'Kim',     'status' => 'declined'],
            ['first' => 'James',   'last' => 'Wilson',  'status' => 'pending'],
            ['first' => 'Amanda',  'last' => 'Liu',     'status' => 'pending'],
        ];

        foreach ($guests as $g) {
            $email = strtolower("{$g['first']}.{$g['last']}@example.com");

            $invite = GuestInvite::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'email' => $email,
                'first_name' => $g['first'],
                'last_name' => $g['last'],
                'invitation_token' => Str::uuid()->toString(),
                'status' => match ($g['status']) {
                    'accepted' => GuestInviteStatus::Responded,
                    'declined' => GuestInviteStatus::Declined,
                    default => GuestInviteStatus::Sent,
                },
                'rsvp_response' => match ($g['status']) {
                    'accepted' => RsvpResponse::Accepted,
                    'declined' => RsvpResponse::Declined,
                    default => null,
                },
                'sent_at' => now()->subDays(14),
                'responded_at' => $g['status'] !== 'pending' ? now()->subDays(5) : null,
                'plus_one_limit' => 1,
            ]);

            if ($g['status'] === 'accepted') {
                $registration = Registration::query()->create([
                    'organization_id' => $org->id,
                    'event_id' => $event->id,
                    'ticket_type_id' => $ticket->id,
                    'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                    'status' => RegistrationStatus::Confirmed,
                    'attendee_first_name' => $g['first'],
                    'attendee_last_name' => $g['last'],
                    'attendee_email' => $email,
                    'guest_invite_id' => $invite->id,
                    'rsvp_response' => RsvpResponse::Accepted,
                    'custom_fields' => [],
                ]);

                $invite->update(['registration_id' => $registration->id]);
                $qrService->generate($registration);
            }
        }
    }

    private function seedWaitlistEntries(Organization $org, Event $event, TicketType $ticket): void
    {
        $waitlistPeople = [
            ['first' => 'Sophia', 'last' => 'Martinez', 'email' => 'sophia.martinez@example.com'],
            ['first' => 'Liam',   'last' => 'Johnson',  'email' => 'liam.johnson@example.com'],
        ];

        foreach ($waitlistPeople as $person) {
            WaitingListEntry::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'ticket_type_id' => $ticket->id,
                'attendee_first_name' => $person['first'],
                'attendee_last_name' => $person['last'],
                'attendee_email' => $person['email'],
                'status' => WaitingListStatus::Waiting,
                'custom_fields' => [],
            ]);
        }
    }
}
