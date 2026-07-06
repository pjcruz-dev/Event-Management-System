<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;

final class OrganizationPolicy
{
    public function __construct(
        private readonly OrganizationRoleService $organizationRoleService,
    ) {}

    public function view(User $user, Organization $organization): bool
    {
        return $user->isMemberOf($organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $organization,
            'settings.manage',
        );
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $organization,
            'members.manage',
        );
    }

    public function inviteMembers(User $user, Organization $organization): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $organization,
            'members.invite',
        );
    }

    public function manageRoles(User $user, Organization $organization): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $organization,
            'members.manage',
        );
    }
}
