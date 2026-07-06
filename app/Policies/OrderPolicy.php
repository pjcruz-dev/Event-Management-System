<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Services\TenantContext;

final class OrderPolicy extends BelongsToTenantPolicy
{
    public function __construct(
        private readonly OrganizationRoleService $organizationRoleService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->userCanAccessTenantResource($user, $order)
            && $this->hasPermission($user, 'orders.view');
    }

    public function refund(User $user, Order $order): bool
    {
        return $this->userCanAccessTenantResource($user, $order)
            && $this->hasPermission($user, 'orders.refund');
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
