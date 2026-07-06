<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketReservationStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Converted = 'converted';
}
