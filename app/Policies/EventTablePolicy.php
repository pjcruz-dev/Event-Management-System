<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventTable;
use App\Models\User;

final class EventTablePolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, EventTable $eventTable): bool
    {
        return $this->canViewEventChild($user, $eventTable);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, EventTable $eventTable): bool
    {
        return $this->canManageEventChild($user, $eventTable);
    }

    public function delete(User $user, EventTable $eventTable): bool
    {
        return $this->canManageEventChild($user, $eventTable);
    }
}
