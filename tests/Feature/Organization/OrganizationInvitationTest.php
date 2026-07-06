<?php

declare(strict_types=1);

namespace Tests\Feature\Organization;

use App\Actions\Organization\CreateOrganizationAction;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\OrganizationRoleService;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class OrganizationInvitationTest extends TestCase
{
    public function test_owner_can_create_organization(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/organizations', [
            'name' => 'Acme Events',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Acme Events');

        $organization = Organization::query()->first();
        $this->assertNotNull($organization);
        $this->assertTrue($user->fresh()->isMemberOf($organization));
    }

    public function test_invite_accept_flow_adds_member(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Acme Events');
        Sanctum::actingAs($owner);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/organizations/{$organization->id}/invitations", [
                'email' => 'teammate@example.com',
                'role' => 'member',
            ])
            ->assertCreated();

        Notification::assertSentOnDemand(InvitationNotification::class);

        $invitation = $organization->invitations()->first();
        $this->assertNotNull($invitation);

        $member = User::factory()->create([
            'email' => 'teammate@example.com',
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($member);

        $this->postJson("/api/v1/invitations/{$invitation->token}/accept")
            ->assertOk();

        $this->assertTrue($member->fresh()->isMemberOf($organization));
    }

    public function test_member_cannot_invite_but_admin_can(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $organization = app(CreateOrganizationAction::class)->handle($owner, 'Acme Events');

        $member = User::factory()->create(['email_verified_at' => now()]);
        $organization->members()->attach($member->id, [
            'role' => 'member',
            'status' => 'active',
        ]);
        app(OrganizationRoleService::class)->assignRole($member, $organization, 'member');

        Sanctum::actingAs($member);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/organizations/{$organization->id}/invitations", [
                'email' => 'blocked@example.com',
                'role' => 'member',
            ])
            ->assertForbidden();

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $organization->members()->attach($admin->id, [
            'role' => 'admin',
            'status' => 'active',
        ]);
        app(OrganizationRoleService::class)->assignRole($admin, $organization, 'admin');

        Sanctum::actingAs($admin);

        Notification::fake();

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/v1/organizations/{$organization->id}/invitations", [
                'email' => 'allowed@example.com',
                'role' => 'member',
            ])
            ->assertCreated();
    }

    public function test_user_from_organization_a_cannot_access_organization_b(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);

        $orgA = app(CreateOrganizationAction::class)->handle($userA, 'Org A');
        $orgB = app(CreateOrganizationAction::class)->handle($userB, 'Org B');

        $tokenA = $userA->createToken('test')->plainTextToken;

        $this->withToken($tokenA)
            ->withHeader('X-Organization-Id', (string) $orgB->id)
            ->getJson("/api/v1/organizations/{$orgB->id}")
            ->assertForbidden();

        Sanctum::actingAs($userA);

        $this->getJson("/api/v1/organizations/{$orgB->id}")
            ->assertForbidden();
    }
}
