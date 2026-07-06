<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketTypeVisibility: string
{
    case Public = 'public';
    case Hidden = 'hidden';
}
