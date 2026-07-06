<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventSession;
use App\Models\User;

final class EventSessionPolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, EventSession $eventSession): bool
    {
        return $this->canViewEventChild($user, $eventSession);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, EventSession $eventSession): bool
    {
        return $this->canManageEventChild($user, $eventSession);
    }

    public function delete(User $user, EventSession $eventSession): bool
    {
        return $this->canManageEventChild($user, $eventSession);
    }
}
