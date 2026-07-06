<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Organization\CreateOrganizationAction;
use App\Exceptions\TenantContextUnresolvedException;
use App\Models\TenantFixture;
use App\Models\User;
use App\Services\TenantContext;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    public function test_queries_only_return_rows_for_resolved_tenant(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $orgA = app(CreateOrganizationAction::class)->handle($userA, 'Org A');
        $orgB = app(CreateOrganizationAction::class)->handle($userB, 'Org B');

        $fixtureA = TenantFixture::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgA->id,
            'label' => 'Fixture A',
        ]);

        $fixtureB = TenantFixture::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgB->id,
            'label' => 'Fixture B',
        ]);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($orgA);

        $visible = TenantFixture::query()->pluck('id')->all();

        $this->assertSame([$fixtureA->id], $visible);
        $this->assertNotContains($fixtureB->id, $visible);
    }

    public function test_create_auto_assigns_organization_id_from_tenant_context(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($user, 'Org A');

        app(TenantContext::class)->set($organization);

        $fixture = TenantFixture::query()->create([
            'label' => 'Auto-assigned',
        ]);

        $this->assertSame($organization->id, $fixture->organization_id);
    }

    public function test_query_without_tenant_context_throws(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($user, 'Org A');

        TenantFixture::withoutTenantScope('seed test data')->create([
            'organization_id' => $organization->id,
            'label' => 'Fixture',
        ]);

        app(TenantContext::class)->clear();

        $this->expectException(TenantContextUnresolvedException::class);

        TenantFixture::query()->get();
    }

    public function test_without_tenant_scope_is_the_only_cross_tenant_escape_hatch(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $orgA = app(CreateOrganizationAction::class)->handle($userA, 'Org A');
        $orgB = app(CreateOrganizationAction::class)->handle($userB, 'Org B');

        TenantFixture::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgA->id,
            'label' => 'Fixture A',
        ]);

        TenantFixture::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgB->id,
            'label' => 'Fixture B',
        ]);

        app(TenantContext::class)->set($orgA);

        $scopedCount = TenantFixture::query()->count();
        $this->assertSame(1, $scopedCount);

        $crossTenantCount = TenantFixture::withoutTenantScope('isolation test')->count();
        $this->assertSame(2, $crossTenantCount);
    }

    public function test_tenant_fixture_api_returns_only_current_tenant_rows(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $orgA = app(CreateOrganizationAction::class)->handle($userA, 'Org A');
        $orgB = app(CreateOrganizationAction::class)->handle($userB, 'Org B');

        TenantFixture::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgA->id,
            'label' => 'Visible',
        ]);

        TenantFixture::withoutTenantScope('seed test data')->create([
            'organization_id' => $orgB->id,
            'label' => 'Hidden',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->withHeader('X-Organization-Id', (string) $orgA->id)
            ->getJson('/api/v1/tenant-fixtures');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Visible');
    }

    public function test_tenant_fixture_create_assigns_current_tenant(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($user, 'Org A');

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson('/api/v1/tenant-fixtures', [
                'label' => 'Created via API',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.organization_id', $organization->id)
            ->assertJsonPath('data.label', 'Created via API');
    }
}
