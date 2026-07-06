<?php

declare(strict_types=1);

namespace Tests\Feature\PublicWebsite;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PublicEventSeoTest extends TestCase
{
    public function test_public_event_api_exposes_seo_fields_for_metadata(): void
    {
        $event = $this->seedSeoEvent();

        $this->getJson("/api/v1/public/events/{$event->slug}")
            ->assertOk()
            ->assertJsonPath('data.event.meta_title', 'Global Innovation Summit 2026')
            ->assertJsonPath('data.event.meta_description', 'Join industry leaders for three days of keynotes and workshops.')
            ->assertJsonPath('data.event.name', 'Global Innovation Summit');
    }

    public function test_public_event_html_contains_server_rendered_meta_when_frontend_available(): void
    {
        $frontendUrl = env('FRONTEND_TEST_URL', env('FRONTEND_URL'));
        if (! is_string($frontendUrl) || $frontendUrl === '') {
            $this->markTestSkipped('Set FRONTEND_TEST_URL or FRONTEND_URL to run SSR HTML SEO checks.');
        }

        $event = $this->seedSeoEvent();

        $response = Http::timeout(10)->get(rtrim($frontendUrl, '/').'/e/'.$event->slug);
        if (! $response->successful()) {
            $this->markTestSkipped('Frontend is not reachable at FRONTEND_TEST_URL.');
        }

        $html = $response->body();

        $this->assertStringContainsString('<title>Global Innovation Summit 2026</title>', $html);
        $this->assertStringContainsString(
            'content="Join industry leaders for three days of keynotes and workshops."',
            $html,
        );
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Event"', preg_replace('/\s+/', '', $html) ?: $html);
    }

    private function seedSeoEvent(): Event
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'SEO Org');

        return Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Global Innovation Summit',
            'slug' => 'global-innovation-seo-'.$organization->id,
            'description' => 'A flagship conference for product and engineering leaders.',
            'category' => EventCategory::Conference,
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'venue' => 'Moscone Center, San Francisco',
            'timezone' => 'UTC',
            'meta_title' => 'Global Innovation Summit 2026',
            'meta_description' => 'Join industry leaders for three days of keynotes and workshops.',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDays(2),
            'published_at' => now(),
        ]);
    }
}
