<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Track;
use App\Models\User;

final class TrackPolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, Track $track): bool
    {
        return $this->canViewEventChild($user, $track);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, Track $track): bool
    {
        return $this->canManageEventChild($user, $track);
    }

    public function delete(User $user, Track $track): bool
    {
        return $this->canManageEventChild($user, $track);
    }
}
