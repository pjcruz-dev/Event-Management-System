<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TicketType;
use App\Models\User;

final class TicketTypePolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, TicketType $ticketType): bool
    {
        return $this->canViewEventChild($user, $ticketType);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, TicketType $ticketType): bool
    {
        return $this->canManageEventChild($user, $ticketType);
    }

    public function delete(User $user, TicketType $ticketType): bool
    {
        return $this->canManageEventChild($user, $ticketType);
    }
}
