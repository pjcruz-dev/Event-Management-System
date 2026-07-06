<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Events\RegistrationCancelled;
use App\Events\RegistrationCheckedIn;
use App\Events\RegistrationConfirmed;
use App\Models\Event;
use App\Models\Registration;
use App\Services\DashboardMetricsService;

final class InvalidateDashboardMetricsCache
{
    public function __construct(
        private readonly DashboardMetricsService $metricsService,
    ) {}

    public function handle(OrderPaid|RegistrationCheckedIn|RegistrationConfirmed|RegistrationCancelled $event): void
    {
        $dashboardEvent = $this->resolveEvent($event);

        if ($dashboardEvent === null) {
            return;
        }

        $this->metricsService->invalidateForEvent($dashboardEvent);
    }

    private function resolveEvent(OrderPaid|RegistrationCheckedIn|RegistrationConfirmed|RegistrationCancelled $event): ?Event
    {
        if ($event instanceof RegistrationCancelled) {
            return Event::withoutTenantScope('metrics cache invalidation')
                ->find($event->eventId);
        }

        if ($event instanceof RegistrationConfirmed) {
            $registration = Registration::withoutTenantScope('metrics cache invalidation')
                ->find($event->registrationId);

            return $registration?->event;
        }

        if ($event instanceof OrderPaid) {
            return $event->order->loadMissing('event')->event;
        }

        return $event->registration->loadMissing('event')->event;
    }
}
