<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Services\TenantContext;

final class EventPolicy extends BelongsToTenantPolicy
{
    public function __construct(
        private readonly OrganizationRoleService $organizationRoleService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, Event $event): bool
    {
        return $this->userCanAccessTenantResource($user, $event)
            && $this->hasPermission($user, 'events.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.create');
    }

    public function update(User $user, Event $event): bool
    {
        return $this->userCanAccessTenantResource($user, $event)
            && $this->hasPermission($user, 'events.manage');
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->userCanAccessTenantResource($user, $event)
            && $this->hasPermission($user, 'events.manage');
    }

    public function publish(User $user, Event $event): bool
    {
        return $this->userCanAccessTenantResource($user, $event)
            && $this->hasPermission($user, 'events.publish');
    }

    public function archive(User $user, Event $event): bool
    {
        return $this->userCanAccessTenantResource($user, $event)
            && $this->hasPermission($user, 'events.manage');
    }

    public function duplicate(User $user, Event $event): bool
    {
        return $this->userCanAccessTenantResource($user, $event)
            && $this->hasPermission($user, 'events.create');
    }

    private function hasPermission(User $user, string $permission): bool
    {
        $organization = $this->tenantContext->get();

        if ($organization === null) {
            return false;
        }

        return $this->organizationRoleService->userHasPermission(
            $user,
            $organization,
            $permission,
        );
    }
}
