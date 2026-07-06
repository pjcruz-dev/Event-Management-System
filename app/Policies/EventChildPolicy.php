<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

abstract class EventChildPolicy extends BelongsToTenantPolicy
{
    public function __construct(
        protected readonly OrganizationRoleService $organizationRoleService,
        protected readonly TenantContext $tenantContext,
    ) {}

    protected function canManageEventChild(User $user, Model $model): bool
    {
        if (! $this->userCanAccessTenantResource($user, $model)) {
            return false;
        }

        return $this->hasPermission($user, 'events.manage');
    }

    protected function canViewEventChild(User $user, Model $model): bool
    {
        if (! $this->userCanAccessTenantResource($user, $model)) {
            return false;
        }

        return $this->hasPermission($user, 'events.view');
    }

    protected function canManageEvent(User $user, Event $event): bool
    {
        if (! $this->userCanAccessTenantResource($user, $event)) {
            return false;
        }

        return $this->hasPermission($user, 'events.manage');
    }

    protected function hasPermission(User $user, string $permission): bool
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
