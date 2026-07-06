<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

final class CouponPolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $this->canViewEventChild($user, $coupon);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $this->canManageEventChild($user, $coupon);
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->canManageEventChild($user, $coupon);
    }
}
