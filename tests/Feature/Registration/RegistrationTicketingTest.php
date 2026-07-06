<?php

declare(strict_types=1);

namespace Tests\Feature\Registration;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\OrderStatus;
use App\Enums\TicketTypeVisibility;
use App\Models\Event;
use App\Models\RegistrationForm;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RegistrationTicketingTest extends TestCase
{
    public function test_organizer_can_crud_ticket_types(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Summit',
            'slug' => 'summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
        ]);

        $create = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/ticket-types", [
                'name' => 'VIP Pass',
                'type_tag' => 'VIP',
                'price' => 199,
                'currency' => 'USD',
                'quantity' => 50,
                'visibility' => 'public',
            ]);

        $create->assertCreated()->assertJsonPath('data.name', 'VIP Pass');

        $ticketTypeId = $create->json('data.id');

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$event->id}/ticket-types/{$ticketTypeId}", [
                'name' => 'VIP Pass Updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'VIP Pass Updated');
    }

    public function test_hidden_ticket_only_visible_via_dedicated_link(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Summit',
            'slug' => 'public-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Hall A',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $public = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 0,
            'currency' => 'USD',
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $hidden = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Invite Only',
            'price' => 99,
            'currency' => 'USD',
            'visibility' => TicketTypeVisibility::Hidden,
            'is_active' => true,
        ]);

        $listing = $this->getJson('/api/v1/public/events/public-summit');
        $listing->assertOk();
        $ids = collect($listing->json('data.ticket_types'))->pluck('id')->all();
        $this->assertContains($public->id, $ids);
        $this->assertNotContains($hidden->id, $ids);

        $withLink = $this->getJson('/api/v1/public/events/public-summit?ticket='.$hidden->id);
        $withLink->assertOk();
        $linkedIds = collect($withLink->json('data.ticket_types'))->pluck('id')->all();
        $this->assertContains($hidden->id, $linkedIds);
    }

    public function test_dynamic_form_rejects_missing_required_custom_field(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Summit',
            'slug' => 'form-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Hall A',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticketType = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 0,
            'currency' => 'USD',
            'quantity' => 10,
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        RegistrationForm::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'fields' => [
                [
                    'key' => 'company',
                    'type' => 'text',
                    'label' => 'Company',
                    'required' => true,
                    'options' => [],
                ],
            ],
        ]);

        $this->postJson('/api/v1/public/events/form-summit/register', [
            'ticket_type_id' => $ticketType->id,
            'quantity' => 1,
            'attendee_first_name' => 'Jane',
            'attendee_last_name' => 'Doe',
            'attendee_email' => 'jane@example.com',
            'custom_fields' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['custom_fields.company']);
    }

    public function test_valid_coupon_reduces_total(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Summit',
            'slug' => 'coupon-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Hall A',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticketType = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Paid',
            'price' => 100,
            'currency' => 'USD',
            'quantity' => 10,
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $event->coupons()->create([
            'organization_id' => $organization->id,
            'code' => 'SAVE20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/public/events/coupon-summit/apply-coupon', [
            'code' => 'SAVE20',
            'ticket_type_id' => $ticketType->id,
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('data.subtotal', 100)
            ->assertJsonPath('data.discount_total', 20)
            ->assertJsonPath('data.total', 80);
    }

    public function test_oversell_prevention_and_waiting_list(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Summit',
            'slug' => 'sellout-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Hall A',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticketType = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Limited',
            'price' => 50,
            'currency' => 'USD',
            'quantity' => 1,
            'quantity_sold' => 0,
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $first = $this->postJson('/api/v1/public/events/sellout-summit/register', [
            'ticket_type_id' => $ticketType->id,
            'quantity' => 1,
            'attendee_first_name' => 'First',
            'attendee_last_name' => 'Buyer',
            'attendee_email' => 'first@example.com',
        ]);

        $first->assertCreated()
            ->assertJsonPath('data.status', 'pending_payment')
            ->assertJsonPath('data.order.status', 'pending_payment');

        $second = $this->postJson('/api/v1/public/events/sellout-summit/register', [
            'ticket_type_id' => $ticketType->id,
            'quantity' => 1,
            'attendee_first_name' => 'Second',
            'attendee_last_name' => 'Buyer',
            'attendee_email' => 'second@example.com',
        ]);

        $second->assertStatus(202)
            ->assertJsonPath('data.status', 'waitlisted');
    }

    public function test_parallel_registration_attempts_only_one_order_for_single_quantity(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Summit',
            'slug' => 'race-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Hall A',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticketType = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Limited',
            'price' => 25,
            'currency' => 'USD',
            'quantity' => 1,
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
        ]);

        $pendingOrders = 0;
        $waitlisted = 0;

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/public/events/race-summit/register', [
                'ticket_type_id' => $ticketType->id,
                'quantity' => 1,
                'attendee_first_name' => 'User',
                'attendee_last_name' => (string) $i,
                'attendee_email' => "user{$i}@example.com",
            ]);

            if ($response->json('data.status') === 'pending_payment') {
                $pendingOrders++;
            }

            if ($response->json('data.status') === 'waitlisted') {
                $waitlisted++;
            }
        }

        $this->assertSame(1, $pendingOrders);
        $this->assertSame(4, $waitlisted);
        $this->assertSame(1, \App\Models\Order::withoutTenantScope('count')->where('status', OrderStatus::PendingPayment)->count());
    }
}
