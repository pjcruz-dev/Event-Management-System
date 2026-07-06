<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\TenantContext;
use Tests\TestCase;

final class TenantModelIsolationTest extends TestCase
{
    public function test_event_queries_respect_tenant_scope(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $orgA = app(CreateOrganizationAction::class)->handle($userA, 'Org A');
        $orgB = app(CreateOrganizationAction::class)->handle($userB, 'Org B');

        Event::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A Summit',
            'slug' => 'org-a-summit',
            'status' => EventStatus::Draft,
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        Event::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Conference',
            'slug' => 'org-b-conference',
            'status' => EventStatus::Draft,
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addDay(),
        ]);

        app(TenantContext::class)->set($orgA);

        $names = Event::query()->pluck('name')->all();

        $this->assertSame(['Org A Summit'], $names);
    }
}
