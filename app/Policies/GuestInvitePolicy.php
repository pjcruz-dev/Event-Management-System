<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GuestInvite;
use App\Models\User;

final class GuestInvitePolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, GuestInvite $guestInvite): bool
    {
        return $this->canViewEventChild($user, $guestInvite);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, GuestInvite $guestInvite): bool
    {
        return $this->canManageEventChild($user, $guestInvite);
    }

    public function delete(User $user, GuestInvite $guestInvite): bool
    {
        return $this->canManageEventChild($user, $guestInvite);
    }
}
