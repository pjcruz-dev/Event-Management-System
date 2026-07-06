<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Waitlisted = 'waitlisted';
}
