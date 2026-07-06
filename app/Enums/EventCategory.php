<?php

declare(strict_types=1);

namespace App\Enums;

enum EventCategory: string
{
    case Conference = 'conference';
    case Workshop = 'workshop';
    case Concert = 'concert';
    case Meetup = 'meetup';
    case Webinar = 'webinar';
    case Festival = 'festival';
    case Other = 'other';
}
