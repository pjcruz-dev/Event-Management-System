<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

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
use App\Services\ReportExportService;
use App\Services\TenantContext;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ReportExportTest extends TestCase
{
    public function test_org_a_cannot_export_org_b_event(): void
    {
        [$orgA, $eventA, $ownerA] = $this->seedExportFixture('Org A');
        [$orgB, $eventB] = array_slice($this->seedExportFixture('Org B'), 0, 2);

        Sanctum::actingAs($ownerA);

        $this->withHeader('X-Organization-Id', (string) $orgA->id)
            ->get("/api/v1/events/{$eventB->id}/exports/registrations?format=csv")
            ->assertNotFound();
    }

    public function test_registrations_csv_export_streams_expected_headers(): void
    {
        [$organization, $event, $owner] = $this->seedExportFixture('Export Org');

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->get("/api/v1/events/{$event->id}/exports/registrations?format=csv");

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('registration_number', $content);
        $this->assertStringContainsString('REG-EXPORT-'.$organization->id, $content);
    }

    public function test_export_service_uses_cursor_not_all(): void
    {
        $service = app(ReportExportService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('registrationRows');
        $method->setAccessible(true);

        [$organization, $event] = array_slice($this->seedExportFixture('Cursor Org'), 0, 2);

        $generator = $method->invoke($service, $event);
        $this->assertInstanceOf(\Generator::class, $generator);

        $source = new \ReflectionMethod($service, 'registrationRows');
        $filename = $source->getFileName();
        $startLine = $source->getStartLine();
        $endLine = $source->getEndLine();
        $lines = array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1);
        $body = implode('', $lines);

        $this->assertStringContainsString('->cursor()', $body);
        $this->assertStringNotContainsString('::all()', $body);
    }

    /**
     * @return array{0: \App\Models\Organization, 1: Event, 2: User}
     */
    private function seedExportFixture(string $name): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, $name);
        app(TenantContext::class)->set($organization);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => $name.' Summit',
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.$organization->id,
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'timezone' => 'UTC',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $ticketType = TicketType::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'name' => 'Pass',
            'price' => 20,
            'currency' => 'USD',
            'quantity' => 50,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $registration = Registration::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'registration_number' => 'REG-EXPORT-'.$organization->id,
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => 'Alex',
            'attendee_last_name' => 'Rivera',
            'attendee_email' => 'alex@example.com',
        ]);

        Order::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'order_number' => 'ORD-EXPORT-'.$organization->id,
            'status' => OrderStatus::Paid,
            'subtotal' => 20,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 20,
            'currency' => 'USD',
            'paid_at' => now(),
        ]);

        return [$organization, $event, $owner];
    }
}
