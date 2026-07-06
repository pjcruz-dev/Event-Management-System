<?php

declare(strict_types=1);

namespace Tests\Feature\Event;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EventBuilderTest extends TestCase
{
    public function test_builder_accepts_blocks_with_empty_settings_object(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Builder Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Builder Summit',
            'slug' => 'builder-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $payload = [
            'theme_config' => [
                'primary_color' => '#1E40AF',
                'secondary_color' => '#F59E0B',
                'font' => 'Inter',
                'layout_variant' => 'classic',
            ],
            'landing_page_config' => [
                'blocks' => [
                    ['type' => 'hero', 'settings' => ['headline' => 'Welcome']],
                    ['type' => 'agenda-preview', 'settings' => []],
                    ['type' => 'speakers-preview', 'settings' => []],
                    ['type' => 'sponsors', 'settings' => []],
                    ['type' => 'cta', 'settings' => ['button_label' => 'Register']],
                ],
            ],
        ];

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$event->id}/builder", $payload)
            ->assertOk()
            ->assertJsonPath('data.landing_page_config.blocks.1.type', 'agenda-preview');

        $event->refresh();
        $this->assertSame('agenda-preview', $event->landing_page_config['blocks'][1]['type']);
    }

    public function test_builder_rejects_invalid_speakers_limit(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Limit Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Limit Summit',
            'slug' => 'limit-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$event->id}/builder", [
                'landing_page_config' => [
                    'blocks' => [
                        [
                            'type' => 'speakers-preview',
                            'settings' => ['limit' => 99],
                        ],
                    ],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_builder_rejects_invalid_exhibitors_layout(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Layout Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Layout Summit',
            'slug' => 'layout-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$event->id}/builder", [
                'landing_page_config' => [
                    'blocks' => [
                        [
                            'type' => 'exhibitors',
                            'settings' => ['layout' => 'carousel'],
                        ],
                    ],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_builder_accepts_exhibitors_block(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Exhibitor Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Exhibitor Summit',
            'slug' => 'exhibitor-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$event->id}/builder", [
                'theme_config' => [
                    'primary_color' => '#1E40AF',
                    'secondary_color' => '#F59E0B',
                    'font' => 'Inter',
                    'layout_variant' => 'classic',
                ],
                'landing_page_config' => [
                    'blocks' => [
                        [
                            'type' => 'exhibitors',
                            'visible' => true,
                            'settings' => [
                                'title' => 'Our exhibitors',
                                'limit' => 12,
                                'layout' => 'grid',
                            ],
                        ],
                    ],
                    'tickets' => [
                        'visible' => false,
                        'title' => 'Passes',
                        'position' => 'after_blocks',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.landing_page_config.blocks.0.type', 'exhibitors')
            ->assertJsonPath('data.landing_page_config.tickets.visible', false);
    }

    public function test_builder_strips_script_tags_from_about_body(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Sanitize Org');
        Sanctum::actingAs($owner);

        $event = Event::withoutTenantScope('seed')->create([
            'organization_id' => $organization->id,
            'name' => 'Sanitize Summit',
            'slug' => 'sanitize-summit',
            'timezone' => 'UTC',
            'status' => EventStatus::Draft,
            'visibility' => 'private',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/v1/events/{$event->id}/builder", [
                'theme_config' => [
                    'primary_color' => '#1E40AF',
                    'secondary_color' => '#F59E0B',
                    'font' => 'Inter',
                    'layout_variant' => 'classic',
                ],
                'landing_page_config' => [
                    'blocks' => [
                        [
                            'type' => 'about',
                            'settings' => [
                                'body' => '<p>Hello</p><script>alert(1)</script>',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $event->refresh();
        $body = $event->landing_page_config['blocks'][0]['settings']['body'];
        $this->assertStringContainsString('<p>Hello</p>', $body);
        $this->assertStringNotContainsString('script', $body);
    }
}
