<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Order;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use App\Services\DashboardMetricsService;
use App\Services\TenantContext;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DashboardMetricsTest extends TestCase
{
    public function test_event_metrics_revenue_matches_paid_orders_total(): void
    {
        [$organization, $event, $owner] = $this->seedEventWithOrders(totalOrders: 3, price: 100.00);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/v1/events/{$event->id}/analytics");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $expectedRevenue = Order::query()
            ->where('event_id', $event->id)
            ->where('status', OrderStatus::Paid)
            ->sum('total');

        $this->assertEqualsWithDelta(
            (float) $expectedRevenue,
            (float) $response->json('data.summary.total_revenue'),
            0.01,
        );
        $this->assertSame(3, $response->json('data.summary.confirmed_registrations'));
    }

    public function test_metrics_cache_is_invalidated_after_check_in(): void
    {
        [$organization, $event, $owner, $registration] = $this->seedCheckedInScenario();

        $metricsService = app(DashboardMetricsService::class);
        $before = $metricsService->forEvent($event);
        $this->assertSame(0, $before['summary']['checked_in_count']);

        $registration->update(['checked_in_at' => now(), 'checked_in_by' => $owner->id]);
        event(new \App\Events\RegistrationCheckedIn($registration->fresh(), $owner));

        $after = $metricsService->forEvent($event);
        $this->assertSame(1, $after['summary']['checked_in_count']);
    }

    public function test_organization_analytics_returns_event_rollups(): void
    {
        [$organization, $event, $owner] = $this->seedEventWithOrders(totalOrders: 2, price: 50.00);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/organization/analytics')
            ->assertOk()
            ->assertJsonPath('data.totals.confirmed_registrations', 2)
            ->assertJsonStructure(['data' => ['events', 'totals']]);
    }

    public function test_organization_activity_feed_is_filterable(): void
    {
        [$organization, $event, $owner] = $this->seedEventWithOrders(totalOrders: 1, price: 25.00);

        ActivityLog::query()->create([
            'organization_id' => $organization->id,
            'actor_id' => $owner->id,
            'action' => 'checkin.scan.success',
            'metadata' => ['event_id' => $event->id, 'gate' => 'Main'],
        ]);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/organization/activity?action=checkin.scan.success')
            ->assertOk()
            ->assertJsonPath('data.items.0.action', 'checkin.scan.success');
    }

    /**
     * @return array{0: \App\Models\Organization, 1: Event, 2: User}
     */
    private function seedEventWithOrders(int $totalOrders, float $price): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Metrics Org');
        app(TenantContext::class)->set($organization);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Analytics Summit',
            'slug' => 'analytics-summit-'.$organization->id,
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'timezone' => 'UTC',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticketType = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'General Admission',
            'price' => $price,
            'currency' => 'USD',
            'quantity' => 100,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        for ($i = 0; $i < $totalOrders; $i++) {
            $registration = Registration::withoutTenantScope('seed')->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'ticket_type_id' => $ticketType->id,
                'registration_number' => 'REG-METRICS-'.$i,
                'status' => RegistrationStatus::Confirmed,
                'attendee_first_name' => 'Pat',
                'attendee_last_name' => (string) $i,
                'attendee_email' => "pat{$i}@example.com",
                'custom_fields' => ['country' => 'US', 'city' => 'Chicago'],
            ]);

            $order = Order::withoutTenantScope('seed')->create([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'order_number' => 'ORD-METRICS-'.$organization->id.'-'.$i,
                'status' => OrderStatus::Paid,
                'subtotal' => $price,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => $price,
                'currency' => 'USD',
                'paid_at' => now()->subDays($i + 1),
            ]);
        }

        return [$organization, $event, $owner];
    }

    /**
     * @return array{0: \App\Models\Organization, 1: Event, 2: User, 3: Registration}
     */
    private function seedCheckedInScenario(): array
    {
        [$organization, $event, $owner] = $this->seedEventWithOrders(totalOrders: 1, price: 10.00);
        $registration = Registration::withoutTenantScope('seed')
            ->where('event_id', $event->id)
            ->firstOrFail();

        return [$organization, $event, $owner, $registration];
    }
}
