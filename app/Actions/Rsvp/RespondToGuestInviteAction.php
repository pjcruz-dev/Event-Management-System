<?php

declare(strict_types=1);

namespace App\Actions\Rsvp;

use App\Actions\BaseAction;
use App\Actions\Registration\CreateRegistrationAction;
use App\DTO\Registration\RegistrationCheckoutResult;
use App\Enums\GuestInviteStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RsvpResponse;
use App\Events\RegistrationConfirmed;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Models\Registration;
use App\Models\TicketType;
use App\Notifications\RsvpResponseConfirmationNotification;
use App\Services\DashboardMetricsService;
use App\Services\GuestInviteAccessService;
use App\Services\TenantContext;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RespondToGuestInviteAction extends BaseAction
{
    public function __construct(
        private readonly GuestInviteAccessService $accessService,
        private readonly CreateRegistrationAction $createRegistrationAction,
        private readonly TenantContext $tenantContext,
        private readonly DashboardMetricsService $metricsService,
    ) {}

    /**
     * @param  array{
     *   response: string,
     *   attendee_first_name?: string,
     *   attendee_last_name?: string,
     *   attendee_email?: string,
     *   attendee_phone?: string|null,
     *   custom_fields?: array<string, mixed>|null,
     *   ticket_type_id?: int,
     *   plus_ones?: list<array{first_name: string, last_name?: string|null, email?: string|null}>,
     * }  $data
     */
    public function handle(mixed ...$args): RegistrationCheckoutResult|GuestInvite
    {
        /** @var GuestInvite $invite */
        $invite = $args[0];
        /** @var array<string, mixed> $data */
        $data = $args[1];

        $invite = $this->accessService->resolveForPublicAccess($invite->invitation_token);
        $event = $invite->event;
        $response = RsvpResponse::from((string) $data['response']);
        $settings = $event->resolvedRsvpSettings();

        if ($response === RsvpResponse::Maybe && ! ($settings['allow_maybe_response'] ?? true)) {
            throw ValidationException::withMessages([
                'response' => ['Maybe responses are not accepted for this event.'],
            ]);
        }

        if ($invite->rsvp_response !== null && $invite->status === GuestInviteStatus::Responded) {
            throw ValidationException::withMessages([
                'response' => ['You have already responded to this invitation.'],
            ]);
        }

        if ($response === RsvpResponse::Declined || $response === RsvpResponse::Maybe) {
            return $this->recordNonAcceptResponse($invite, $response);
        }

        return $this->acceptInvite($invite, $data, $settings);
    }

  /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $settings
     */
    private function acceptInvite(GuestInvite $invite, array $data, array $settings): RegistrationCheckoutResult
    {
        $event = $invite->event;
        $this->tenantContext->set($event->organization);

        $plusOnes = $data['plus_ones'] ?? [];
        $maxPlusOnes = $invite->plus_one_limit ?? (int) ($settings['max_plus_ones_per_invite'] ?? 0);

        if (! ($settings['allow_plus_ones'] ?? false)) {
            $plusOnes = [];
        }

        if (count($plusOnes) > $maxPlusOnes) {
            throw ValidationException::withMessages([
                'plus_ones' => ["You may only bring up to {$maxPlusOnes} guest(s)."],
            ]);
        }

        $ticketType = $this->resolveTicketType($event, $data);

        $registrationData = [
            'ticket_type_id' => $ticketType->id,
            'quantity' => 1,
            'attendee_first_name' => $data['attendee_first_name'] ?? $invite->first_name,
            'attendee_last_name' => $data['attendee_last_name'] ?? ($invite->last_name ?? ''),
            'attendee_email' => strtolower((string) ($data['attendee_email'] ?? $invite->email)),
            'attendee_phone' => $data['attendee_phone'] ?? $invite->phone,
            'custom_fields' => $data['custom_fields'] ?? [],
            'guest_invite_id' => $invite->id,
            'rsvp_response' => RsvpResponse::Accepted->value,
            'table_id' => $invite->table_id,
        ];

        return DB::transaction(function () use ($invite, $event, $registrationData, $plusOnes, $ticketType): RegistrationCheckoutResult {
            $result = $this->createRegistrationAction->handle($event, $registrationData, null);

            if ($result->registration === null) {
                return $result;
            }

            $registration = $result->registration;
            $registration->update([
                'guest_invite_id' => $invite->id,
                'rsvp_response' => RsvpResponse::Accepted,
                'table_id' => $invite->table_id,
            ]);

            $invite->update([
                'status' => GuestInviteStatus::Responded,
                'rsvp_response' => RsvpResponse::Accepted,
                'responded_at' => now(),
                'registration_id' => $registration->id,
            ]);

            foreach ($plusOnes as $plusOne) {
                $this->createPlusOneRegistration(
                    $event,
                    $ticketType,
                    $registration,
                    $plusOne,
                );
            }

            $registration->refresh();
            if ($registration->status === RegistrationStatus::Confirmed && $registration->qr_token_hash === null) {
                event(new RegistrationConfirmed($registration));
            }

            $this->notifyResponse($invite, RsvpResponse::Accepted);
            $this->metricsService->invalidateForEvent($event);

            return new RegistrationCheckoutResult(
                registration: $registration->fresh(['order', 'ticketType', 'table']),
                order: $result->order,
            );
        });
    }

    private function recordNonAcceptResponse(GuestInvite $invite, RsvpResponse $response): GuestInvite
    {
        $status = $response === RsvpResponse::Declined
            ? GuestInviteStatus::Declined
            : GuestInviteStatus::Responded;

        $invite->update([
            'status' => $status,
            'rsvp_response' => $response,
            'responded_at' => now(),
        ]);

        $event = Event::withoutTenantScope('rsvp decline metrics')
            ->findOrFail($invite->event_id);

        $this->notifyResponse($invite, $response);
        $this->metricsService->invalidateForEvent($event);

        return GuestInvite::withoutTenantScope('rsvp decline result')
            ->findOrFail($invite->id);
    }

    /**
     * @param  array{first_name: string, last_name?: string|null, email?: string|null}  $plusOne
     */
    private function createPlusOneRegistration(
        \App\Models\Event $event,
        TicketType $ticketType,
        Registration $primary,
        array $plusOne,
    ): void {
        $plusResult = $this->createRegistrationAction->handle($event, [
            'ticket_type_id' => $ticketType->id,
            'quantity' => 1,
            'attendee_first_name' => $plusOne['first_name'],
            'attendee_last_name' => $plusOne['last_name'] ?? '',
            'attendee_email' => $plusOne['email'] ?? $primary->attendee_email,
            'attendee_phone' => null,
            'custom_fields' => [],
            'guest_invite_id' => $primary->guest_invite_id,
            'rsvp_response' => RsvpResponse::Accepted->value,
            'is_plus_one' => true,
            'primary_registration_id' => $primary->id,
            'table_id' => $primary->table_id,
        ], null);

        if ($plusResult->registration !== null) {
            $plusResult->registration->update([
                'is_plus_one' => true,
                'primary_registration_id' => $primary->id,
                'guest_invite_id' => $primary->guest_invite_id,
                'rsvp_response' => RsvpResponse::Accepted,
                'table_id' => $primary->table_id,
            ]);

            $plusResult->registration->refresh();
            if ($plusResult->registration->status === RegistrationStatus::Confirmed
                && $plusResult->registration->qr_token_hash === null) {
                event(new RegistrationConfirmed($plusResult->registration));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveTicketType(\App\Models\Event $event, array $data): TicketType
    {
        $ticketTypes = TicketType::withoutTenantScope('public rsvp ticket resolve')
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->get();

        if ($ticketTypes->isEmpty()) {
            throw ValidationException::withMessages([
                'ticket_type_id' => ['No ticket types are available for this event.'],
            ]);
        }

        $ticketTypeId = $data['ticket_type_id'] ?? $ticketTypes->first()?->id;

        $ticketType = $ticketTypes->firstWhere('id', $ticketTypeId);

        if ($ticketType === null) {
            throw ValidationException::withMessages([
                'ticket_type_id' => ['Invalid ticket type selected.'],
            ]);
        }

        return $ticketType;
    }

    private function notifyResponse(GuestInvite $invite, RsvpResponse $response): void
    {
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $invite->email);
        $notifiable->notify(new RsvpResponseConfirmationNotification($invite, $response));
    }
}
