<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;

final class InvitationPolicy
{
    public function __construct(
        private readonly OrganizationRoleService $organizationRoleService,
    ) {}

    public function viewAny(User $user, Organization $organization): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $organization,
            'members.invite',
        );
    }

    public function create(User $user, Organization $organization): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $organization,
            'members.invite',
        );
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $invitation->organization,
            'members.invite',
        );
    }

    public function resend(User $user, Invitation $invitation): bool
    {
        return $this->organizationRoleService->userHasPermission(
            $user,
            $invitation->organization,
            'members.invite',
        );
    }
}
