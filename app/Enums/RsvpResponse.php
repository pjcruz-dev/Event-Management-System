<?php

declare(strict_types=1);

namespace App\Enums;

enum RsvpResponse: string
{
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Maybe = 'maybe';
}
