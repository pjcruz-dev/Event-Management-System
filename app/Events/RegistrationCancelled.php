<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Registration;
use Illuminate\Foundation\Events\Dispatchable;

final class RegistrationCancelled
{
    use Dispatchable;

    public readonly int $eventId;
    public readonly int $ticketTypeId;
    public readonly int $organizationId;

    public function __construct(Registration $registration)
    {
        $this->eventId = $registration->event_id;
        $this->ticketTypeId = $registration->ticket_type_id;
        $this->organizationId = $registration->organization_id;
    }
}
