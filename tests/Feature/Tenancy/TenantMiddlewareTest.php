<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Organization\CreateOrganizationAction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class TenantMiddlewareTest extends TestCase
{
    public function test_tenant_required_endpoint_fails_closed_without_organization_context(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/tenant-fixtures')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Organization context is required.');
    }

    public function test_resolve_tenant_rejects_non_member_organization(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $orgB = app(CreateOrganizationAction::class)->handle($userB, 'Org B');

        Sanctum::actingAs($userA);

        $this->withHeader('X-Organization-Id', (string) $orgB->id)
            ->getJson('/api/v1/tenant-fixtures')
            ->assertForbidden();
    }

    public function test_resolve_tenant_sets_context_from_organization_route_parameter(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($user, 'Org A');

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $organization->id);
    }
}
