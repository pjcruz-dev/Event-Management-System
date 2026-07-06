<?php

declare(strict_types=1);

namespace App\Enums;

enum EventVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}
