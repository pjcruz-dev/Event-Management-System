<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RegistrationConfirmed;
use App\Models\Organization;
use App\Models\Registration;
use App\Notifications\TicketIssuedNotification;
use App\Services\QrTokenService;
use App\Services\TenantContext;
use App\Services\TicketPdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;

final class GenerateRegistrationQrToken implements ShouldQueue
{
    public function __construct(
        private readonly QrTokenService $qrTokenService,
        private readonly TicketPdfService $ticketPdfService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(RegistrationConfirmed $event): void
    {
        $this->ensureTenantContext($event->organizationId);

        $registration = Registration::query()->find($event->registrationId);

        if ($registration === null) {
            return;
        }

        if ($registration->qr_token_hash !== null) {
            return;
        }

        $registration->loadMissing('event');

        $token = $this->qrTokenService->generate($registration);
        $pdfPath = $this->ticketPdfService->generate($registration, $token);

        // Only send individual email for orderless registrations (RSVP, walk-in, free).
        // Order-based registrations get a consolidated email via SendOrderTicketsNotification.
        if ($registration->order_id === null) {
            $notifiable = new AnonymousNotifiable;
            $notifiable->route('mail', $registration->attendee_email);
            $notifiable->notify(new TicketIssuedNotification($registration, $pdfPath));
        }
    }

    private function ensureTenantContext(int $organizationId): void
    {
        if ($this->tenantContext->isResolved()) {
            return;
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization !== null) {
            $this->tenantContext->set($organization);
        }
    }
}
