<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Exhibitor;
use App\Models\User;

final class ExhibitorPolicy extends EventChildPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'events.view');
    }

    public function view(User $user, Exhibitor $exhibitor): bool
    {
        return $this->canViewEventChild($user, $exhibitor);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'exhibitors.manage');
    }

    public function update(User $user, Exhibitor $exhibitor): bool
    {
        return $this->canManageExhibitor($user, $exhibitor);
    }

    public function delete(User $user, Exhibitor $exhibitor): bool
    {
        return $this->canManageExhibitor($user, $exhibitor);
    }

    private function canManageExhibitor(User $user, Exhibitor $exhibitor): bool
    {
        if (! $this->userCanAccessTenantResource($user, $exhibitor)) {
            return false;
        }

        return $this->hasPermission($user, 'exhibitors.manage');
    }
}
