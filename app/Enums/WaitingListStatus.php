<?php

declare(strict_types=1);

namespace App\Enums;

enum WaitingListStatus: string
{
    case Waiting = 'waiting';
    case Promoted = 'promoted';
    case Cancelled = 'cancelled';
}
