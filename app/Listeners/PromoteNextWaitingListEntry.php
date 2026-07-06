<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Registration\PromoteWaitingListEntryAction;
use App\Enums\WaitingListStatus;
use App\Events\RegistrationCancelled;
use App\Models\Organization;
use App\Models\WaitingListEntry;
use App\Services\TenantContext;
use App\Services\TicketAvailabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class PromoteNextWaitingListEntry implements ShouldQueue
{
    public function __construct(
        private readonly PromoteWaitingListEntryAction $promoteAction,
        private readonly TicketAvailabilityService $ticketAvailability,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(RegistrationCancelled $event): void
    {
        if (! $this->tenantContext->isResolved()) {
            $organization = Organization::query()->find($event->organizationId);
            if ($organization !== null) {
                $this->tenantContext->set($organization);
            }
        }

        $nextEntry = WaitingListEntry::query()
            ->where('event_id', $event->eventId)
            ->where('ticket_type_id', $event->ticketTypeId)
            ->where('status', WaitingListStatus::Waiting)
            ->orderBy('created_at')
            ->first();

        if ($nextEntry === null) {
            return;
        }

        $ticketType = $nextEntry->ticketType;

        if ($ticketType === null) {
            return;
        }

        $remaining = $this->ticketAvailability->remainingQuantity($ticketType);
        if ($remaining !== null && $remaining < 1) {
            return;
        }

        $this->promoteAction->handle($nextEntry);
    }
}
