<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RegistrationCheckedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Registration $registration,
        public User $staff,
    ) {}
}
