<?php

declare(strict_types=1);

namespace App\DTO\Registration;

use App\Models\Order;
use App\Models\Registration;
use App\Models\WaitingListEntry;

final readonly class RegistrationCheckoutResult
{
    public function __construct(
        public ?Registration $registration = null,
        public ?Order $order = null,
        public ?WaitingListEntry $waitingListEntry = null,
    ) {}

    public function isWaitingList(): bool
    {
        return $this->waitingListEntry !== null;
    }
}
