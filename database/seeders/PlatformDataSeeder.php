<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\CouponDiscountType;
use App\Enums\EventCategory;
use App\Enums\EventRegistrationMode;
use App\Enums\EventStatus;
use App\Enums\EventTableShape;
use App\Enums\EventVisibility;
use App\Enums\GuestInviteStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MembershipStatus;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RsvpResponse;
use App\Enums\SessionSpeakerRole;
use App\Enums\SponsorTier;
use App\Enums\TicketTypeVisibility;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\EventTable;
use App\Models\Exhibitor;
use App\Models\GuestInvite;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\RegistrationForm;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\TicketType;
use App\Models\Track;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Services\QrTokenService;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class PlatformDataSeeder extends Seeder
{
    private const DEMO_OWNER_EMAIL = 'demo@event-saas.test';

    public function run(): void
    {
        $createOrganization = app(CreateOrganizationAction::class);
        $roleService = app(OrganizationRoleService::class);
        $tenantContext = app(TenantContext::class);

        $organizationNames = [
            'Summit Events Co',
            'Harbor Conference Group',
            'Pulse Live Experiences',
        ];

        foreach ($organizationNames as $index => $organizationName) {
            $owner = $index === 0
                ? User::factory()->create([
                    'name' => 'Demo Organizer',
                    'email' => self::DEMO_OWNER_EMAIL,
                    'email_verified_at' => now(),
                ])
                : User::factory()->create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'email_verified_at' => now(),
                ]);

            $organization = $createOrganization->handle($owner, $organizationName);

            if ($index === 0) {
                $organization->update([
                    'settings' => [
                        'default_currency' => 'PHP',
                        'public_profile' => [
                            'enabled' => true,
                            'description' => 'We produce flagship conferences, galas, and community experiences across North America.',
                            'website_url' => 'https://example.com/summit-events',
                        ],
                    ],
                ]);
            }

            $tenantContext->set($organization);

            $this->seedOrganizationMembers($organization, $owner, $roleService);

            $eventCount = $index === 0 ? 20 : 2;
            $events = $this->seedEventsForOrganization($organization, $eventCount);

            foreach ($events as $event) {
                match ($event->status) {
                    EventStatus::Published => $this->seedFullPublishedEvent($organization, $event, $owner),
                    EventStatus::Archived => $this->seedArchivedEvent($organization, $event, $owner),
                    default => $this->seedDraftEvent($organization, $event),
                };
            }
        }

        $tenantContext->clear();
    }

    private function seedOrganizationMembers(
        Organization $organization,
        User $owner,
        OrganizationRoleService $roleService,
    ): void {
        $roles = [
            ['role' => 'admin', 'count' => 1],
            ['role' => 'member', 'count' => 3],
        ];

        foreach ($roles as $roleConfig) {
            for ($i = 0; $i < $roleConfig['count']; $i++) {
                $user = User::factory()->create([
                    'email_verified_at' => now(),
                ]);

                $organization->members()->attach($user->id, [
                    'role' => $roleConfig['role'],
                    'status' => MembershipStatus::Active->value,
                    'invited_by' => $owner->id,
                ]);

                $roleService->assignRole($user, $organization, $roleConfig['role']);
            }
        }
    }

    /**
     * @return list<Event>
     */
    private function seedEventsForOrganization(Organization $organization, int $count): array
    {
        $templates = $this->eventTemplates();
        $events = [];

        for ($i = 0; $i < $count; $i++) {
            $template = $templates[$i % count($templates)];
            $startsAt = now()->addMonths($template['months_ahead'])->addDays($i);
            $name = $count === 20 ? $template['name'] : $template['name'].' '.$organization->id;

            $status = $template['status'];
            $isPublished = $status === EventStatus::Published;

            $events[] = Event::query()->create([
                'organization_id' => $organization->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.$organization->id.'-'.($i + 1),
                'description' => $template['description'],
                'venue' => $template['venue'],
                'timezone' => $template['timezone'],
                'capacity' => $template['capacity'],
                'status' => $status,
                'visibility' => $template['visibility'],
                'registration_mode' => $template['registration_mode'],
                'rsvp_settings' => $this->normalizeRsvpSettings($template['rsvp_settings'] ?? null, $startsAt),
                'confirmation_settings' => $template['confirmation_settings'] ?? $this->defaultConfirmationSettings($template),
                'category' => $template['category'],
                'theme_config' => [
                    'primary_color' => $template['primary_color'],
                    'secondary_color' => $template['secondary_color'],
                    'font' => 'Inter',
                    'logo_url' => null,
                    'hero_image_url' => null,
                    'layout_variant' => $template['layout_variant'],
                ],
                'landing_page_config' => [
                    'blocks' => [
                        ['type' => 'hero', 'settings' => (object) [
                            'headline' => $name,
                            'subheadline' => $template['tagline'],
                            'background_type' => 'color',
                        ]],
                        ['type' => 'about', 'settings' => (object) ['body' => $template['description']]],
                        ['type' => 'image', 'settings' => (object) [
                            'caption' => $template['venue'],
                            'alt' => $name,
                        ]],
                        ['type' => 'agenda-preview', 'settings' => (object) []],
                        ['type' => 'speakers-preview', 'settings' => (object) []],
                        ['type' => 'sponsors', 'settings' => (object) []],
                        ['type' => 'exhibitors', 'settings' => (object) []],
                        ['type' => 'faq', 'settings' => (object) [
                            'item_count' => '2',
                            'question_0' => 'Where is the event?',
                            'answer_0' => $template['venue'],
                            'question_1' => 'How do I register?',
                            'answer_1' => $template['registration_mode'] === EventRegistrationMode::Rsvp
                                ? 'Respond to your invitation email.'
                                : 'Choose a ticket type and complete checkout.',
                        ]],
                        ['type' => 'cta', 'settings' => (object) ['button_label' => 'Register now']],
                    ],
                    'tickets' => [
                        'visible' => true,
                        'title' => 'Get your tickets',
                        'position' => 'after_blocks',
                    ],
                ],
                'meta_title' => $isPublished && $template['visibility'] === EventVisibility::Public
                    ? $name.' | Register Now'
                    : null,
                'meta_description' => $isPublished && $template['visibility'] === EventVisibility::Public
                    ? $template['tagline']
                    : null,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addDays($template['duration_days']),
                'published_at' => $isPublished ? now()->subDays(fake()->numberBetween(3, 30)) : null,
            ]);
        }

        return $events;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventTemplates(): array
    {
        return [
            [
                'name' => 'Global Innovation Summit',
                'tagline' => 'Three days of keynotes, workshops, and networking with industry leaders.',
                'description' => 'The premier technology conference for product builders and engineering leaders.',
                'venue' => 'Moscone Center, San Francisco, CA',
                'timezone' => 'America/Los_Angeles',
                'capacity' => 2500,
                'category' => EventCategory::Conference,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 2,
                'duration_days' => 3,
                'primary_color' => '#1E40AF',
                'secondary_color' => '#F59E0B',
                'layout_variant' => 'classic',
            ],
            [
                'name' => 'Annual Charity Gala',
                'tagline' => 'An elegant evening supporting local arts education.',
                'description' => 'Black-tie dinner, live auction, and performances by regional artists.',
                'venue' => 'The Grand Ballroom, Chicago, IL',
                'timezone' => 'America/Chicago',
                'capacity' => 400,
                'category' => EventCategory::Festival,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Rsvp,
                'rsvp_settings' => [
                    'allow_plus_ones' => true,
                    'max_plus_ones_per_invite' => 1,
                    'collect_meal_preferences' => true,
                    'meal_options' => ['Chicken', 'Beef', 'Fish', 'Vegetarian', 'Vegan'],
                    'allow_maybe_response' => true,
                    'response_deadline' => now()->addMonths(3)->toIso8601String(),
                    'auto_send_reminders' => true,
                    'reminder_days_before_deadline' => 7,
                ],
                'confirmation_settings' => [
                    'rsvp_accepted_message' => 'We are delighted to confirm your attendance at the Annual Charity Gala.',
                    'rsvp_declined_message' => 'Thank you for letting us know. We hope to see you at a future event.',
                    'rsvp_maybe_message' => 'We have noted your tentative response. Please confirm when you can.',
                    'registration_pending_message' => null,
                    'registration_confirmed_message' => null,
                ],
                'months_ahead' => 3,
                'duration_days' => 1,
                'primary_color' => '#7C3AED',
                'secondary_color' => '#DB2777',
                'layout_variant' => 'bold',
            ],
            [
                'name' => 'Product Leadership Retreat',
                'tagline' => 'Intensive off-site for VP-level product leaders.',
                'description' => 'Invite-only strategy sessions, case studies, and peer roundtables.',
                'venue' => 'Aspen Meadows Resort, Aspen, CO',
                'timezone' => 'America/Denver',
                'capacity' => 80,
                'category' => EventCategory::Workshop,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::InviteOnly,
                'rsvp_settings' => null,
                'months_ahead' => 4,
                'duration_days' => 2,
                'primary_color' => '#0F766E',
                'secondary_color' => '#14B8A6',
                'layout_variant' => 'minimal',
            ],
            [
                'name' => 'Harbor Jazz Festival',
                'tagline' => 'Waterfront stages, food trucks, and sunset sets.',
                'description' => 'A weekend celebration of jazz, soul, and blues on the harbor.',
                'venue' => 'Inner Harbor, Baltimore, MD',
                'timezone' => 'America/New_York',
                'capacity' => 5000,
                'category' => EventCategory::Concert,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 5,
                'duration_days' => 3,
                'primary_color' => '#B45309',
                'secondary_color' => '#F97316',
                'layout_variant' => 'bold',
            ],
            [
                'name' => 'SaaS Metrics Masterclass',
                'tagline' => 'Half-day workshop on ARR, churn, and unit economics.',
                'description' => 'Hands-on exercises for finance and ops leaders at growth-stage startups.',
                'venue' => 'WeWork Congress, Austin, TX',
                'timezone' => 'America/Chicago',
                'capacity' => 60,
                'category' => EventCategory::Workshop,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 1,
                'duration_days' => 1,
                'primary_color' => '#0369A1',
                'secondary_color' => '#38BDF8',
                'layout_variant' => 'minimal',
            ],
            [
                'name' => 'DevRel Community Meetup',
                'tagline' => 'Monthly gathering for developer advocates and community builders.',
                'description' => 'Lightning talks, open mic demos, and networking over pizza.',
                'venue' => 'GitHub HQ Community Space, San Francisco, CA',
                'timezone' => 'America/Los_Angeles',
                'capacity' => 120,
                'category' => EventCategory::Meetup,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 1,
                'duration_days' => 1,
                'primary_color' => '#4F46E5',
                'secondary_color' => '#818CF8',
                'layout_variant' => 'classic',
            ],
            [
                'name' => 'Healthcare AI Webinar Series',
                'tagline' => 'Live virtual sessions on clinical AI adoption and compliance.',
                'description' => 'CME-eligible webinars for hospital innovation teams.',
                'venue' => 'Online (Zoom)',
                'timezone' => 'America/New_York',
                'capacity' => 1000,
                'category' => EventCategory::Webinar,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 2,
                'duration_days' => 1,
                'primary_color' => '#059669',
                'secondary_color' => '#34D399',
                'layout_variant' => 'minimal',
            ],
            [
                'name' => 'Wedding of Alex & Jordan',
                'tagline' => 'Join us for our celebration in the Napa Valley.',
                'description' => 'Ceremony at sunset followed by dinner and dancing under the stars.',
                'venue' => 'Vineyard Estate, Napa, CA',
                'timezone' => 'America/Los_Angeles',
                'capacity' => 150,
                'category' => EventCategory::Other,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Rsvp,
                'rsvp_settings' => [
                    'allow_plus_ones' => true,
                    'max_plus_ones_per_invite' => 1,
                    'collect_meal_preferences' => true,
                    'meal_options' => ['Chicken', 'Salmon', 'Vegetarian', 'Kids Meal'],
                    'allow_maybe_response' => false,
                    'response_deadline' => now()->addMonths(6)->toIso8601String(),
                    'auto_send_reminders' => true,
                    'reminder_days_before_deadline' => 14,
                ],
                'confirmation_settings' => [
                    'rsvp_accepted_message' => 'We cannot wait to celebrate with you in Napa Valley!',
                    'rsvp_declined_message' => 'We will miss you on our special day.',
                    'rsvp_maybe_message' => null,
                    'registration_pending_message' => null,
                    'registration_confirmed_message' => null,
                ],
                'months_ahead' => 6,
                'duration_days' => 1,
                'primary_color' => '#9D174D',
                'secondary_color' => '#F472B6',
                'layout_variant' => 'classic',
            ],
            [
                'name' => 'Cybersecurity Summit East',
                'tagline' => 'Zero-trust, incident response, and cloud security deep dives.',
                'description' => 'Two tracks for practitioners and CISOs with hands-on labs.',
                'venue' => 'Jacob K. Javits Center, New York, NY',
                'timezone' => 'America/New_York',
                'capacity' => 1800,
                'category' => EventCategory::Conference,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 4,
                'duration_days' => 2,
                'primary_color' => '#1E293B',
                'secondary_color' => '#64748B',
                'layout_variant' => 'classic',
            ],
            [
                'name' => 'Startup Pitch Night',
                'tagline' => 'Ten founders, five minutes each, one grand prize.',
                'description' => 'Community pitch competition with angel investor judges.',
                'venue' => 'Capital Factory, Austin, TX',
                'timezone' => 'America/Chicago',
                'capacity' => 200,
                'category' => EventCategory::Meetup,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 1,
                'duration_days' => 1,
                'primary_color' => '#DC2626',
                'secondary_color' => '#FBBF24',
                'layout_variant' => 'bold',
            ],
            [
                'name' => 'Design Systems Unconference',
                'tagline' => 'Participant-driven sessions for design ops and UI engineers.',
                'description' => 'Open agenda built morning-of; bring your Figma and code questions.',
                'venue' => 'PNCA, Portland, OR',
                'timezone' => 'America/Los_Angeles',
                'capacity' => 90,
                'category' => EventCategory::Workshop,
                'status' => EventStatus::Draft,
                'visibility' => EventVisibility::Private,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 7,
                'duration_days' => 1,
                'primary_color' => '#7C2D12',
                'secondary_color' => '#FB923C',
                'layout_variant' => 'minimal',
            ],
            [
                'name' => 'Nonprofit Board Dinner',
                'tagline' => 'Annual appreciation dinner for board members and major donors.',
                'description' => 'Private seated dinner with program updates and recognition awards.',
                'venue' => 'The Ritz-Carlton, Boston, MA',
                'timezone' => 'America/New_York',
                'capacity' => 75,
                'category' => EventCategory::Other,
                'status' => EventStatus::Draft,
                'visibility' => EventVisibility::Private,
                'registration_mode' => EventRegistrationMode::InviteOnly,
                'rsvp_settings' => null,
                'months_ahead' => 8,
                'duration_days' => 1,
                'primary_color' => '#1C1917',
                'secondary_color' => '#A8A29E',
                'layout_variant' => 'classic',
            ],
            [
                'name' => 'Summer Food & Wine Festival',
                'tagline' => 'Tastings from 40 local chefs and regional wineries.',
                'description' => 'Outdoor festival with live cooking demos and family activities.',
                'venue' => 'Centennial Olympic Park, Atlanta, GA',
                'timezone' => 'America/New_York',
                'capacity' => 3000,
                'category' => EventCategory::Festival,
                'status' => EventStatus::Draft,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 9,
                'duration_days' => 2,
                'primary_color' => '#CA8A04',
                'secondary_color' => '#EAB308',
                'layout_variant' => 'bold',
            ],
            [
                'name' => 'HR Compliance Workshop',
                'tagline' => '2026 employment law updates for multi-state employers.',
                'description' => 'Full-day workshop with attorneys and HRIS implementation tips.',
                'venue' => 'Marriott Marquis, Washington, DC',
                'timezone' => 'America/New_York',
                'capacity' => 150,
                'category' => EventCategory::Workshop,
                'status' => EventStatus::Draft,
                'visibility' => EventVisibility::Private,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 3,
                'duration_days' => 1,
                'primary_color' => '#4338CA',
                'secondary_color' => '#A5B4FC',
                'layout_variant' => 'minimal',
            ],
            [
                'name' => 'Indie Game Showcase',
                'tagline' => 'Play unreleased titles and meet studio founders.',
                'description' => 'Arcade-style expo for independent game developers.',
                'venue' => 'LA Convention Center, Los Angeles, CA',
                'timezone' => 'America/Los_Angeles',
                'capacity' => 800,
                'category' => EventCategory::Festival,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 5,
                'duration_days' => 2,
                'primary_color' => '#7E22CE',
                'secondary_color' => '#C084FC',
                'layout_variant' => 'bold',
            ],
            [
                'name' => 'Executive Roundtable: Retail',
                'tagline' => 'Closed-door forum for retail CIOs and CMOs.',
                'description' => 'Chatham House rules discussion on omnichannel and loyalty.',
                'venue' => 'Four Seasons, Miami, FL',
                'timezone' => 'America/New_York',
                'capacity' => 40,
                'category' => EventCategory::Meetup,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Private,
                'registration_mode' => EventRegistrationMode::InviteOnly,
                'rsvp_settings' => null,
                'months_ahead' => 2,
                'duration_days' => 1,
                'primary_color' => '#0E7490',
                'secondary_color' => '#22D3EE',
                'layout_variant' => 'minimal',
            ],
            [
                'name' => 'Climate Tech Forum',
                'tagline' => 'Investors, founders, and policymakers on the energy transition.',
                'description' => 'Panel discussions and startup demos in clean energy and carbon markets.',
                'venue' => 'Climate Pledge Arena, Seattle, WA',
                'timezone' => 'America/Los_Angeles',
                'capacity' => 1200,
                'category' => EventCategory::Conference,
                'status' => EventStatus::Archived,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => -6,
                'duration_days' => 2,
                'primary_color' => '#15803D',
                'secondary_color' => '#4ADE80',
                'layout_variant' => 'classic',
            ],
            [
                'name' => 'Regional Sales Kickoff 2025',
                'tagline' => 'Last year\'s SKO — sessions, awards, and territory planning.',
                'description' => 'Archived internal sales kickoff with recorded keynotes.',
                'venue' => 'Gaylord Opryland, Nashville, TN',
                'timezone' => 'America/Chicago',
                'capacity' => 600,
                'category' => EventCategory::Conference,
                'status' => EventStatus::Archived,
                'visibility' => EventVisibility::Private,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => -12,
                'duration_days' => 3,
                'primary_color' => '#1D4ED8',
                'secondary_color' => '#60A5FA',
                'layout_variant' => 'classic',
            ],
            [
                'name' => 'Fundraiser: Midnight Run',
                'tagline' => '5K night run supporting youth homelessness programs.',
                'description' => 'Chip-timed race, post-run celebration, and silent auction.',
                'venue' => 'Lakefront Trail, Chicago, IL',
                'timezone' => 'America/Chicago',
                'capacity' => 2000,
                'category' => EventCategory::Festival,
                'status' => EventStatus::Archived,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => -3,
                'duration_days' => 1,
                'primary_color' => '#BE123C',
                'secondary_color' => '#FB7185',
                'layout_variant' => 'bold',
            ],
            [
                'name' => 'Partner Enablement Day',
                'tagline' => 'Training and certification for certified implementation partners.',
                'description' => 'Product deep dives, certification exams, and partner networking.',
                'venue' => 'Denver Convention Center, Denver, CO',
                'timezone' => 'America/Denver',
                'capacity' => 350,
                'category' => EventCategory::Conference,
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'registration_mode' => EventRegistrationMode::Open,
                'rsvp_settings' => null,
                'months_ahead' => 3,
                'duration_days' => 1,
                'primary_color' => '#2563EB',
                'secondary_color' => '#93C5FD',
                'layout_variant' => 'classic',
            ],
        ];
    }

    private function seedBasicTicketTypes(Organization $organization, Event $event): void
    {
        TicketType::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Guest Admission',
            'description' => 'Standard guest admission.',
            'price' => 0,
            'currency' => 'PHP',
            'quantity' => 500,
            'visibility' => \App\Enums\TicketTypeVisibility::Public,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function seedOpenEventTicketTypes(Organization $organization, Event $event): void
    {
        $capacity = $event->capacity ?? 200;
        $isFree = fake()->boolean(30);

        TicketType::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => $isFree ? 'Free General Admission' : 'Standard',
            'description' => $isFree
                ? 'Complimentary entry to the event.'
                : 'General admission with full access.',
            'price' => $isFree ? 0 : fake()->randomElement([19.00, 29.00, 49.00, 79.00, 99.00]),
            'currency' => 'PHP',
            'quantity' => (int) round($capacity * 0.7),
            'visibility' => \App\Enums\TicketTypeVisibility::Public,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        if (! $isFree) {
            TicketType::query()->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'name' => 'Premium',
                'description' => 'Priority seating and exclusive networking access.',
                'price' => fake()->randomElement([149.00, 199.00, 249.00, 349.00]),
                'currency' => 'PHP',
                'quantity' => (int) round($capacity * 0.2),
                'visibility' => \App\Enums\TicketTypeVisibility::Public,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        TicketType::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'VIP',
            'description' => 'All-access VIP pass with exclusive perks.',
            'price' => fake()->randomElement([199.00, 399.00, 599.00, 799.00]),
            'currency' => 'PHP',
            'quantity' => (int) round($capacity * 0.1),
            'type_tag' => 'VIP',
            'visibility' => \App\Enums\TicketTypeVisibility::Public,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    private function seedFullPublishedEvent(Organization $organization, Event $event, User $owner): void
    {
        $this->seedRegistrationForm($organization, $event);

        if ($event->registration_mode === EventRegistrationMode::Rsvp) {
            $this->seedBasicTicketTypes($organization, $event);
            $this->seedSeating($organization, $event);
            $this->seedGuestInvites($organization, $event, $owner);

            if ($this->isConferenceStyle($event)) {
                $this->seedConferenceContent($organization, $event);
            }

            return;
        }

        if ($event->registration_mode === EventRegistrationMode::InviteOnly) {
            $this->seedBasicTicketTypes($organization, $event);
            $this->seedSeating($organization, $event);
            $this->seedGuestInvites($organization, $event, $owner, inviteOnly: true);

            return;
        }

        $this->seedOpenEventTicketTypes($organization, $event);
        $this->seedCoupons($organization, $event);

        if ($this->isConferenceStyle($event)) {
            $this->seedConferenceContent($organization, $event);
        }

        $registrationCount = match ($event->category) {
            EventCategory::Conference => 25,
            EventCategory::Festival, EventCategory::Concert => 18,
            EventCategory::Meetup, EventCategory::Workshop => 12,
            EventCategory::Webinar => 30,
            default => 10,
        };

        $this->seedRegistrationsAndOrders($organization, $event, $owner, $registrationCount);
    }

    private function seedDraftEvent(Organization $organization, Event $event): void
    {
        $this->seedRegistrationForm($organization, $event, partial: true);

        if ($event->registration_mode !== EventRegistrationMode::InviteOnly) {
            $this->seedOpenEventTicketTypes($organization, $event);
            $this->seedCoupons($organization, $event, activeOnly: false);
        }

        if ($this->isConferenceStyle($event)) {
            $this->seedConferenceContent($organization, $event, sessionCount: 1);
        }
    }

    private function seedArchivedEvent(Organization $organization, Event $event, User $owner): void
    {
        $this->seedRegistrationForm($organization, $event);
        $this->seedOpenEventTicketTypes($organization, $event);

        if ($this->isConferenceStyle($event)) {
            $this->seedConferenceContent($organization, $event);
        }

        $this->seedRegistrationsAndOrders($organization, $event, $owner, 20, allCheckedIn: true);
    }

    private function isConferenceStyle(Event $event): bool
    {
        return in_array($event->category, [
            EventCategory::Conference,
            EventCategory::Workshop,
            EventCategory::Webinar,
            EventCategory::Festival,
            EventCategory::Concert,
        ], true);
    }

    /**
     * @return array<string, string|null>
     */
    private function defaultConfirmationSettings(array $template): array
    {
        $name = $template['name'];

        return [
            'rsvp_accepted_message' => null,
            'rsvp_declined_message' => null,
            'rsvp_maybe_message' => null,
            'registration_pending_message' => 'Thank you for registering for '.$name.'. Complete payment to secure your spot.',
            'registration_confirmed_message' => 'Your tickets for '.$name.' are confirmed. See you there!',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>|null
     */
    private function normalizeRsvpSettings(?array $settings, \Illuminate\Support\Carbon $startsAt): ?array
    {
        if ($settings === null) {
            return null;
        }

        $defaults = [
            'allow_plus_ones' => false,
            'max_plus_ones_per_invite' => 0,
            'collect_meal_preferences' => false,
            'meal_options' => ['Chicken', 'Fish', 'Vegetarian', 'Vegan'],
            'allow_maybe_response' => true,
            'response_deadline' => $startsAt->copy()->subDays(7)->toIso8601String(),
            'auto_send_reminders' => false,
            'reminder_days_before_deadline' => 3,
        ];

        return array_merge($defaults, $settings);
    }

    private function seedRegistrationForm(Organization $organization, Event $event, bool $partial = false): void
    {
        $fields = [
            [
                'key' => 'company',
                'type' => 'text',
                'label' => 'Company / Organization',
                'required' => false,
                'options' => [],
            ],
            [
                'key' => 'job_title',
                'type' => 'text',
                'label' => 'Job Title',
                'required' => false,
                'options' => [],
            ],
        ];

        if (! $partial) {
            $fields[] = [
                'key' => 'dietary_restrictions',
                'type' => 'select',
                'label' => 'Dietary Restrictions',
                'required' => false,
                'options' => ['None', 'Vegetarian', 'Vegan', 'Gluten-free', 'Halal', 'Kosher'],
            ];
            $fields[] = [
                'key' => 't_shirt_size',
                'type' => 'select',
                'label' => 'T-Shirt Size',
                'required' => false,
                'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            ];
            $fields[] = [
                'key' => 'accessibility_needs',
                'type' => 'textarea',
                'label' => 'Accessibility Needs',
                'required' => false,
                'options' => [],
            ];
        }

        RegistrationForm::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'fields' => $fields,
        ]);
    }

    private function seedCoupons(Organization $organization, Event $event, bool $activeOnly = true): void
    {
        $paidTicketIds = $event->ticketTypes()
            ->where('price', '>', 0)
            ->pluck('id')
            ->all();

        if ($paidTicketIds === []) {
            return;
        }

        Coupon::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'code' => 'EARLY20',
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => 20,
            'applicable_ticket_type_ids' => $paidTicketIds,
            'max_uses' => 100,
            'times_used' => fake()->numberBetween(3, 15),
            'valid_from' => now()->subWeek(),
            'valid_until' => $event->starts_at?->copy()->subDay(),
            'is_active' => $activeOnly,
        ]);

        Coupon::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'code' => 'SAVE500',
            'discount_type' => CouponDiscountType::Fixed,
            'discount_value' => 500,
            'applicable_ticket_type_ids' => $paidTicketIds,
            'max_uses' => 50,
            'times_used' => fake()->numberBetween(1, 8),
            'valid_from' => now()->subWeek(),
            'valid_until' => $event->starts_at?->copy()->subDay(),
            'is_active' => $activeOnly,
        ]);

        Coupon::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'code' => 'VIP50',
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => 50,
            'applicable_ticket_type_ids' => $event->ticketTypes()->where('type_tag', 'VIP')->pluck('id')->all() ?: null,
            'max_uses' => 10,
            'times_used' => fake()->numberBetween(0, 3),
            'valid_from' => now()->subWeek(),
            'valid_until' => $event->starts_at?->copy()->subDay(),
            'is_active' => $activeOnly,
        ]);
    }

    private function seedSeating(Organization $organization, Event $event): void
    {
        $tables = [
            ['name' => 'Table 1', 'capacity' => 10, 'shape' => EventTableShape::Round],
            ['name' => 'Table 2', 'capacity' => 10, 'shape' => EventTableShape::Round],
            ['name' => 'Table 3', 'capacity' => 8, 'shape' => EventTableShape::Round],
            ['name' => 'Head Table', 'capacity' => 6, 'shape' => EventTableShape::Head],
            ['name' => 'Table 4', 'capacity' => 10, 'shape' => EventTableShape::Rectangle],
        ];

        foreach ($tables as $index => $table) {
            EventTable::query()->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'name' => $table['name'],
                'capacity' => $table['capacity'],
                'sort_order' => $index,
                'shape' => $table['shape'],
                'x' => 100 + ($index * 120),
                'y' => 200 + (($index % 2) * 80),
                'rotation' => 0,
            ]);
        }
    }

    private function seedConferenceContent(
        Organization $organization,
        Event $event,
        int $sessionCount = 3,
    ): void {
        $tracks = collect([
            'Main Stage',
            'Product Track',
            'Workshops',
        ])->map(fn (string $name, int $index) => Track::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => $name,
            'description' => fake()->sentence(),
            'sort_order' => $index,
        ]));

        $speakers = Speaker::factory()
            ->count(6)
            ->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
            ]);

        foreach ($tracks as $trackIndex => $track) {
            for ($sessionIndex = 0; $sessionIndex < $sessionCount; $sessionIndex++) {
                $startsAt = $event->starts_at->copy()->setTime(9 + ($trackIndex * 2) + $sessionIndex, 0);

                $session = EventSession::query()->create([
                    'organization_id' => $organization->id,
                    'event_id' => $event->id,
                    'track_id' => $track->id,
                    'title' => fake()->randomElement([
                        'Opening Keynote',
                        'Panel: Industry Trends',
                        'Hands-on Workshop',
                        'Fireside Chat',
                    ]),
                    'description' => fake()->paragraph(),
                    'room' => fake()->randomElement(['Hall A', 'Room 201', 'Studio B']),
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHour(),
                    'capacity' => 250,
                    'sort_order' => $sessionIndex,
                ]);

                $selectedSpeakers = $speakers->random(2);

                foreach ($selectedSpeakers as $sortOrder => $speaker) {
                    $session->speakers()->attach($speaker->id, [
                        'role' => SessionSpeakerRole::Speaker->value,
                        'sort_order' => $sortOrder,
                    ]);
                }
            }
        }

        Sponsor::factory()->count(4)->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
        ]);

        Sponsor::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Northwind Partners',
            'tier' => SponsorTier::Platinum,
            'website_url' => 'https://example.com/northwind',
            'sort_order' => 0,
        ]);

        $exhibitors = Exhibitor::factory()->count(5)->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
        ]);

        foreach ($exhibitors as $index => $exhibitor) {
            $exhibitor->booth()->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'code' => 'B'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'location' => 'Expo Floor A',
            ]);
        }
    }

    private function seedGuestInvites(
        Organization $organization,
        Event $event,
        User $owner,
        bool $inviteOnly = false,
    ): void {
        $ticketType = TicketType::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->first();

        if ($ticketType === null) {
            return;
        }

        $qrService = app(QrTokenService::class);
        $mealOptions = $event->resolvedRsvpSettings()['meal_options'] ?? ['Chicken', 'Fish', 'Vegetarian', 'Vegan'];
        $tables = EventTable::query()->where('event_id', $event->id)->get();

        $guests = [
            ['first_name' => 'Sarah', 'last_name' => 'Chen', 'status' => 'accepted', 'household' => 'The Chen Family', 'group' => 'Donors'],
            ['first_name' => 'David', 'last_name' => 'Park', 'status' => 'accepted', 'household' => 'The Chen Family', 'group' => 'Donors'],
            ['first_name' => 'Emily', 'last_name' => 'Brooks', 'status' => 'accepted', 'household' => 'Brooks Household', 'group' => 'Board'],
            ['first_name' => 'Michael', 'last_name' => 'Torres', 'status' => 'declined', 'household' => 'Torres Family', 'group' => 'VIP'],
            ['first_name' => 'Rachel', 'last_name' => 'Kim', 'status' => 'maybe', 'household' => 'Kim Family', 'group' => 'Press'],
            ['first_name' => 'James', 'last_name' => 'Wilson', 'status' => 'pending', 'household' => 'Wilson Family', 'group' => 'General'],
            ['first_name' => 'Amanda', 'last_name' => 'Liu', 'status' => 'pending', 'household' => 'Liu Family', 'group' => 'General'],
            ['first_name' => 'Robert', 'last_name' => 'Singh', 'status' => 'accepted', 'household' => 'Singh Household', 'group' => 'Sponsors'],
            ['first_name' => 'Lisa', 'last_name' => 'Martinez', 'status' => 'accepted', 'household' => 'Martinez Family', 'group' => 'Donors'],
            ['first_name' => 'Tom', 'last_name' => 'Anderson', 'status' => 'sent', 'household' => 'Anderson Family', 'group' => 'General'],
        ];

        if ($inviteOnly) {
            $guests = array_slice($guests, 0, 6);
        }

        foreach ($guests as $index => $guest) {
            $email = strtolower($guest['first_name'].'.'.$guest['last_name'].'@example.com');
            $table = $tables->isNotEmpty() ? $tables[$index % $tables->count()] : null;

            $invite = GuestInvite::query()->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'email' => $email,
                'first_name' => $guest['first_name'],
                'last_name' => $guest['last_name'],
                'invitation_token' => Str::uuid()->toString(),
                'status' => match ($guest['status']) {
                    'accepted' => GuestInviteStatus::Responded,
                    'declined' => GuestInviteStatus::Declined,
                    'maybe' => GuestInviteStatus::Responded,
                    'sent' => GuestInviteStatus::Sent,
                    default => GuestInviteStatus::Sent,
                },
                'rsvp_response' => match ($guest['status']) {
                    'accepted' => RsvpResponse::Accepted,
                    'declined' => RsvpResponse::Declined,
                    'maybe' => RsvpResponse::Maybe,
                    default => null,
                },
                'household_name' => $guest['household'],
                'group_label' => $guest['group'],
                'table_id' => $table?->id,
                'sent_at' => now()->subDays(fake()->numberBetween(7, 21)),
                'responded_at' => in_array($guest['status'], ['accepted', 'declined', 'maybe'], true)
                    ? now()->subDays(fake()->numberBetween(1, 6))
                    : null,
                'last_reminder_sent_at' => $guest['status'] === 'pending'
                    ? now()->subDays(2)
                    : null,
                'reminder_count' => $guest['status'] === 'pending' ? 1 : 0,
                'plus_one_limit' => ($event->rsvp_settings['allow_plus_ones'] ?? false) ? 1 : 0,
            ]);

            if ($guest['status'] === 'accepted') {
                $meal = $mealOptions[array_rand($mealOptions)];

                $registration = Registration::query()->create([
                    'organization_id' => $organization->id,
                    'event_id' => $event->id,
                    'ticket_type_id' => $ticketType->id,
                    'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                    'status' => RegistrationStatus::Confirmed,
                    'attendee_first_name' => $guest['first_name'],
                    'attendee_last_name' => $guest['last_name'],
                    'attendee_email' => $email,
                    'guest_invite_id' => $invite->id,
                    'rsvp_response' => RsvpResponse::Accepted,
                    'table_id' => $table?->id,
                    'custom_fields' => [
                        'meal_preference' => $meal,
                        'company' => fake()->company(),
                    ],
                    'checked_in_at' => fake()->boolean(40) ? now()->subHours(fake()->numberBetween(1, 48)) : null,
                    'checked_in_by' => fake()->boolean(40) ? $owner->id : null,
                    'check_in_gate' => fake()->boolean(40) ? 'Main entrance' : null,
                ]);

                $invite->update(['registration_id' => $registration->id]);
                $qrService->generate($registration);
            }
        }
    }

    private function seedRegistrationsAndOrders(
        Organization $organization,
        Event $event,
        User $owner,
        int $count = 25,
        bool $allCheckedIn = false,
    ): void {
        $ticketTypes = $event->ticketTypes()->get();

        if ($ticketTypes->isEmpty()) {
            return;
        }

        $qrService = app(QrTokenService::class);
        $dietaryOptions = ['Chicken', 'Fish', 'Vegetarian', 'Vegan', 'None'];
        $refundSeeded = false;

        for ($i = 0; $i < $count; $i++) {
            $ticketType = $ticketTypes->random();
            $price = (float) $ticketType->price;
            $isPending = ! $allCheckedIn && $i < 2 && $price > 0;
            $tax = round($price * 0.08, 2);
            $total = round($price + $tax, 2);

            $shouldCheckIn = $allCheckedIn || fake()->boolean(55);
            $mealChoice = $dietaryOptions[array_rand($dietaryOptions)];

            $registration = Registration::query()->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'user_id' => fake()->boolean(30) ? User::factory()->create(['email_verified_at' => now()])->id : null,
                'ticket_type_id' => $ticketType->id,
                'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                'status' => $isPending ? RegistrationStatus::Pending : RegistrationStatus::Confirmed,
                'attendee_first_name' => fake()->firstName(),
                'attendee_last_name' => fake()->lastName(),
                'attendee_email' => fake()->unique()->safeEmail(),
                'attendee_phone' => fake()->optional()->phoneNumber(),
                'custom_fields' => [
                    'company' => fake()->company(),
                    'job_title' => fake()->jobTitle(),
                    'dietary_restrictions' => $mealChoice,
                    'meal_preference' => $mealChoice,
                    't_shirt_size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
                ],
                'checked_in_at' => $shouldCheckIn && ! $isPending ? now()->subHours(fake()->numberBetween(1, 72)) : null,
                'checked_in_by' => $shouldCheckIn && ! $isPending ? $owner->id : null,
                'check_in_gate' => $shouldCheckIn && ! $isPending ? fake()->randomElement(['Main entrance', 'VIP gate', 'Side entrance']) : null,
            ]);

            $orderStatus = $isPending ? OrderStatus::PendingPayment : OrderStatus::Paid;
            if (! $refundSeeded && ! $isPending && $price > 0 && $i === $count - 1) {
                $orderStatus = OrderStatus::Refunded;
                $refundSeeded = true;
                $registration->update(['status' => RegistrationStatus::Cancelled]);
            }

            $order = \App\Models\Order::query()->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'user_id' => $registration->user_id,
                'registration_id' => $registration->id,
                'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
                'status' => $orderStatus,
                'subtotal' => $price,
                'discount_total' => 0,
                'tax_total' => $tax,
                'total' => $total,
                'currency' => 'PHP',
                'payment_method' => $price > 0 ? 'stripe' : null,
                'paid_at' => $orderStatus === OrderStatus::Paid ? now()->subDays(fake()->numberBetween(1, 14)) : null,
                'refund_amount' => $orderStatus === OrderStatus::Refunded ? $total : null,
                'refunded_at' => $orderStatus === OrderStatus::Refunded ? now()->subDays(2) : null,
                'refund_reason' => $orderStatus === OrderStatus::Refunded ? 'Customer requested cancellation' : null,
            ]);

            $registration->update(['order_id' => $order->id]);

            $order->items()->create([
                'organization_id' => $organization->id,
                'ticket_type_id' => $ticketType->id,
                'registration_id' => $registration->id,
                'description' => $ticketType->name,
                'quantity' => 1,
                'unit_price' => $price,
                'total_price' => $price,
            ]);

            if ($price > 0 && $orderStatus === OrderStatus::Paid) {
                $order->invoice()->create([
                    'organization_id' => $organization->id,
                    'invoice_number' => 'INV-'.strtoupper((string) Str::ulid()),
                    'status' => InvoiceStatus::Paid,
                    'subtotal' => $price,
                    'tax_total' => $tax,
                    'total' => $total,
                    'currency' => 'PHP',
                    'issued_at' => $order->paid_at,
                    'due_at' => $order->paid_at?->copy()->addDays(14),
                    'paid_at' => $order->paid_at,
                ]);
            }

            if ($registration->status === RegistrationStatus::Confirmed) {
                $qrService->generate($registration);
            }

            $ticketType->increment('quantity_sold');
        }

        $this->seedMultiTicketOrder($organization, $event, $ticketTypes, $owner, $qrService);
    }

    private function seedMultiTicketOrder(
        Organization $organization,
        Event $event,
        \Illuminate\Support\Collection $ticketTypes,
        User $owner,
        QrTokenService $qrService,
    ): void {
        $paidTicket = $ticketTypes->first(fn (TicketType $t) => (float) $t->price > 0);

        if ($paidTicket === null) {
            return;
        }

        $quantity = 3;
        $price = (float) $paidTicket->price;
        $subtotal = $price * $quantity;
        $tax = round($subtotal * 0.08, 2);
        $total = round($subtotal + $tax, 2);
        $email = 'group.buyer@example.com';

        $order = \App\Models\Order::query()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
            'status' => OrderStatus::Paid,
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => $tax,
            'total' => $total,
            'currency' => 'PHP',
            'payment_method' => 'stripe',
            'paid_at' => now()->subDays(3),
        ]);

        $order->items()->create([
            'organization_id' => $organization->id,
            'ticket_type_id' => $paidTicket->id,
            'description' => $paidTicket->name,
            'quantity' => $quantity,
            'unit_price' => $price,
            'total_price' => $subtotal,
        ]);

        for ($q = 0; $q < $quantity; $q++) {
            $registration = Registration::query()->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'ticket_type_id' => $paidTicket->id,
                'order_id' => $order->id,
                'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
                'status' => RegistrationStatus::Confirmed,
                'attendee_first_name' => fake()->firstName(),
                'attendee_last_name' => fake()->lastName(),
                'attendee_email' => $q === 0 ? $email : fake()->unique()->safeEmail(),
                'custom_fields' => ['company' => 'Group Purchase Co'],
                'checked_in_at' => $q < 2 ? now()->subHours(4) : null,
                'checked_in_by' => $q < 2 ? $owner->id : null,
                'check_in_gate' => $q < 2 ? 'Main entrance' : null,
            ]);

            $qrService->generate($registration);
            $paidTicket->increment('quantity_sold');
        }

        if ($order->registration_id === null) {
            $firstReg = Registration::query()->where('order_id', $order->id)->first();
            $order->update(['registration_id' => $firstReg?->id]);
        }
    }
}
