<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sponsor;
use App\Models\User;

final class SponsorPolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, Sponsor $sponsor): bool
    {
        return $this->canViewEventChild($user, $sponsor);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, Sponsor $sponsor): bool
    {
        return $this->canManageEventChild($user, $sponsor);
    }

    public function delete(User $user, Sponsor $sponsor): bool
    {
        return $this->canManageEventChild($user, $sponsor);
    }
}
