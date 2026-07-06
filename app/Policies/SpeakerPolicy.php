<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Speaker;
use App\Models\User;

final class SpeakerPolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, Speaker $speaker): bool
    {
        return $this->canViewEventChild($user, $speaker);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'events.manage');
    }

    public function update(User $user, Speaker $speaker): bool
    {
        return $this->canManageEventChild($user, $speaker);
    }

    public function delete(User $user, Speaker $speaker): bool
    {
        return $this->canManageEventChild($user, $speaker);
    }
}
