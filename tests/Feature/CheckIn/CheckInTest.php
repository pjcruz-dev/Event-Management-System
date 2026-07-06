<?php

declare(strict_types=1);

namespace Tests\Feature\CheckIn;

use App\Actions\Order\FulfillPaidOrderAction;
use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Services\QrTokenService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CheckInTest extends TestCase
{
    public function test_valid_scan_checks_in_paid_registration_once(): void
    {
        [$organization, $event, $staff, $registration, $token] = $this->seedPaidRegistration(typeTag: 'General');

        Sanctum::actingAs($staff);

        $first = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/checkin/scan", [
                'token' => $token,
                'gate' => 'Main entrance',
            ]);

        $first->assertOk()
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.is_vip', false);

        $registration->refresh();
        $this->assertNotNull($registration->checked_in_at);
        $this->assertSame($staff->id, $registration->checked_in_by);

        $second = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/checkin/scan", [
                'token' => $token,
            ]);

        $second->assertStatus(409)
            ->assertJsonPath('data.status', 'duplicate')
            ->assertJsonStructure(['data' => ['duplicate_info' => ['checked_in_at', 'checked_in_by']]]);
    }

    public function test_vip_ticket_is_flagged_in_scan_response(): void
    {
        [$organization, $event, $staff, , $token] = $this->seedPaidRegistration(typeTag: 'VIP');

        Sanctum::actingAs($staff);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/checkin/scan", ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.is_vip', true);
    }

    public function test_unpaid_registration_is_rejected(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');
        $event = $this->createPublishedEvent($organization);
        $ticketType = $this->createTicketType($organization, $event);

        $registration = Registration::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'registration_number' => 'REG-TEST-UNPAID',
            'status' => RegistrationStatus::Pending,
            'attendee_first_name' => 'Pat',
            'attendee_last_name' => 'Lee',
            'attendee_email' => 'pat@example.com',
        ]);

        Order::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'order_number' => 'ORD-TEST-UNPAID',
            'status' => OrderStatus::PendingPayment,
            'subtotal' => 50,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 50,
            'currency' => 'USD',
        ]);

        $token = app(QrTokenService::class)->generate($registration);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/checkin/scan", ['token' => $token])
            ->assertStatus(422)
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_invalid_token_is_rejected_without_leaking_details(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');
        $event = $this->createPublishedEvent($organization);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/checkin/scan", ['token' => 'not-a-valid-token'])
            ->assertStatus(422)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.registration', null);
    }

    public function test_batch_sync_resolves_duplicate_offline_scans_deterministically(): void
    {
        [$organization, $event, $staff, , $token] = $this->seedPaidRegistration();

        Sanctum::actingAs($staff);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/events/{$event->id}/checkin/sync-batch", [
                'items' => [
                    [
                        'token' => $token,
                        'idempotency_key' => 'device-a',
                        'scanned_at' => now()->subMinute()->toIso8601String(),
                        'gate' => 'Gate A',
                    ],
                    [
                        'token' => $token,
                        'idempotency_key' => 'device-b',
                        'scanned_at' => now()->toIso8601String(),
                        'gate' => 'Gate B',
                    ],
                ],
            ]);

        $response->assertOk();

        $items = collect($response->json('data.items'));
        $this->assertSame('success', $items->firstWhere('idempotency_key', 'device-a')['status']);
        $this->assertSame('duplicate', $items->firstWhere('idempotency_key', 'device-b')['status']);
    }

    /**
     * @return array{0: \App\Models\Organization, 1: Event, 2: User, 3: Registration, 4: string}
     */
    private function seedPaidRegistration(string $typeTag = 'General'): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Org A');
        $event = $this->createPublishedEvent($organization);
        $ticketType = $this->createTicketType($organization, $event, $typeTag);

        $registration = Registration::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'registration_number' => 'REG-'.strtoupper(uniqid()),
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => 'Alex',
            'attendee_last_name' => 'Kim',
            'attendee_email' => 'alex@example.com',
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

        app(FulfillPaidOrderAction::class)->handle($order);
        $token = app(QrTokenService::class)->generate($registration->fresh());

        return [$organization, $event, $owner, $registration->fresh(), $token];
    }

    private function createPublishedEvent(\App\Models\Organization $organization): Event
    {
        return Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Check-In Summit',
            'slug' => 'checkin-summit-'.uniqid(),
            'timezone' => 'UTC',
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Hall A',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);
    }

    private function createTicketType(
        \App\Models\Organization $organization,
        Event $event,
        string $typeTag = 'General',
    ): TicketType {
        return TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => $typeTag.' Pass',
            'type_tag' => $typeTag,
            'price' => 0,
            'currency' => 'USD',
            'quantity' => 100,
            'quantity_sold' => 0,
            'per_order_limit' => 5,
            'visibility' => 'public',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
