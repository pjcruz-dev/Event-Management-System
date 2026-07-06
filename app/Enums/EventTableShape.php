<?php

declare(strict_types=1);

namespace App\Enums;

enum EventTableShape: string
{
    case Round = 'round';
    case Rectangle = 'rectangle';
    case Head = 'head';
}
