<?php

declare(strict_types=1);

namespace App\Enums;

enum GuestInviteStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Opened = 'opened';
    case Responded = 'responded';
    case Declined = 'declined';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
