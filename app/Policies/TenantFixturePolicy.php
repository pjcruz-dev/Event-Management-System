<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantFixture;
use App\Models\User;

final class TenantFixturePolicy extends BelongsToTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TenantFixture $tenantFixture): bool
    {
        return $this->userCanAccessTenantResource($user, $tenantFixture);
    }

    public function create(User $user): bool
    {
        return true;
    }
}
