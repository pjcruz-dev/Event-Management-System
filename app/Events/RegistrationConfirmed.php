<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Registration;
use Illuminate\Foundation\Events\Dispatchable;

final class RegistrationConfirmed
{
    use Dispatchable;

    public readonly int $registrationId;
    public readonly int $organizationId;

    public function __construct(Registration $registration)
    {
        $this->registrationId = $registration->id;
        $this->organizationId = $registration->organization_id;
    }
}
