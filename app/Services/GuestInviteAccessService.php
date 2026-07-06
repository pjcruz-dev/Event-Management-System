<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GuestInviteStatus;
use App\Enums\RsvpResponse;
use App\Models\Event;
use App\Models\EventTable;
use App\Models\GuestInvite;
use Illuminate\Support\Str;

final class GuestInviteAccessService
{
    public function findByToken(string $token): ?GuestInvite
    {
        $invite = GuestInvite::withoutTenantScope('public rsvp token lookup')
            ->where('invitation_token', $token)
            ->first();

        if ($invite === null) {
            return null;
        }

        $invite->setRelation(
            'event',
            Event::withoutTenantScope('public rsvp event')->find($invite->event_id),
        );

        if ($invite->table_id !== null) {
            $invite->setRelation(
                'table',
                EventTable::withoutTenantScope('public rsvp table')->find($invite->table_id),
            );
        }

        return $invite;
    }

    public function resolveForPublicAccess(string $token): GuestInvite
    {
        $invite = $this->findByToken($token);

        if ($invite === null || ! $invite->isRespondable()) {
            abort(404, 'Invitation not found.');
        }

        if ($this->isPastDeadline($invite->event)) {
            abort(404, 'The RSVP deadline has passed.');
        }

        return $invite;
    }

    public function validateForPublicRegistration(Event $event, ?string $token): void
    {
        if (! $event->requiresInvitationToken()) {
            return;
        }

        if ($token === null || $token === '') {
            abort(403, 'A valid invitation is required to register for this event.');
        }

        $invite = $this->findByToken($token);

        if ($invite === null || $invite->event_id !== $event->id || ! $invite->isRespondable()) {
            abort(403, 'The invitation token is invalid or has expired.');
        }

        if ($invite->rsvp_response === RsvpResponse::Declined) {
            abort(403, 'This invitation has been declined.');
        }

        if ($this->isPastDeadline($event)) {
            abort(403, 'The RSVP deadline has passed.');
        }
    }

    public function markOpened(GuestInvite $invite): void
    {
        if ($invite->opened_at !== null) {
            return;
        }

        $invite->update([
            'opened_at' => now(),
            'status' => $invite->status === GuestInviteStatus::Sent
                ? GuestInviteStatus::Opened
                : $invite->status,
        ]);
    }

    public function isPastDeadline(Event $event): bool
    {
        $settings = $event->resolvedRsvpSettings();
        $deadline = $settings['response_deadline'] ?? null;

        if ($deadline === null) {
            return false;
        }

        return now()->greaterThan(\Carbon\Carbon::parse($deadline));
    }

    public function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (GuestInvite::withoutTenantScope('token uniqueness')->where('invitation_token', $token)->exists());

        return $token;
    }
}
