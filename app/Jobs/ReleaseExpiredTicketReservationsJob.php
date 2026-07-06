<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\TicketAvailabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ReleaseExpiredTicketReservationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(TicketAvailabilityService $ticketAvailability): void
    {
        $ticketAvailability->releaseExpiredReservations();
    }
}
