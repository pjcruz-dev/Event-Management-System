<?php

declare(strict_types=1);

namespace App\Enums;

enum EventRegistrationMode: string
{
    case Open = 'open';
    case InviteOnly = 'invite_only';
    case Rsvp = 'rsvp';
}
