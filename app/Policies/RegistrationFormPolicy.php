<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RegistrationForm;
use App\Models\User;

final class RegistrationFormPolicy extends EventChildPolicy
{
    public function view(User $user, RegistrationForm $registrationForm): bool
    {
        return $this->canViewEventChild($user, $registrationForm);
    }

    public function update(User $user, RegistrationForm $registrationForm): bool
    {
        return $this->canManageEventChild($user, $registrationForm);
    }
}
