<?php

declare(strict_types=1);

namespace App\Enums;

enum SponsorTier: string
{
    case Platinum = 'platinum';
    case Gold = 'gold';
    case Silver = 'silver';
    case Bronze = 'bronze';
    case Partner = 'partner';
}
