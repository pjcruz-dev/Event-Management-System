<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionSpeakerRole: string
{
    case Keynote = 'keynote';
    case Speaker = 'speaker';
    case Panelist = 'panelist';
    case Moderator = 'moderator';
}
