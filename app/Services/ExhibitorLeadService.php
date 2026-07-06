<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Exhibitor;
use App\Models\ExhibitorContact;
use App\Models\ExhibitorLead;
use App\Models\Registration;
use Illuminate\Validation\ValidationException;

final class ExhibitorLeadService
{
    public function __construct(
        private readonly QrTokenService $qrTokenService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function capture(
        ExhibitorContact $contact,
        string $token,
        ?string $notes = null,
    ): ExhibitorLead {
        $this->tenantContext->set($contact->organization);

        $registration = $this->qrTokenService->verify($token);

        if ($registration === null || $registration->event_id !== $contact->event_id) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or unrecognized attendee badge.'],
            ]);
        }

        if ($registration->status === RegistrationStatus::Cancelled
            || $registration->status === RegistrationStatus::Waitlisted) {
            throw ValidationException::withMessages([
                'token' => ['This attendee is not eligible for lead capture.'],
            ]);
        }

        $registration->loadMissing('order');

        if ($registration->order === null || $registration->order->status !== OrderStatus::Paid) {
            throw ValidationException::withMessages([
                'token' => ['This attendee has not completed registration payment.'],
            ]);
        }

        $existing = ExhibitorLead::query()
            ->where('exhibitor_id', $contact->exhibitor_id)
            ->where('registration_id', $registration->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return ExhibitorLead::query()->create([
            'organization_id' => $contact->organization_id,
            'event_id' => $contact->event_id,
            'exhibitor_id' => $contact->exhibitor_id,
            'registration_id' => $registration->id,
            'scanned_by' => $contact->id,
            'notes' => $notes,
            'scanned_at' => now(),
        ]);
    }

    public function assertSameExhibitor(ExhibitorContact $contact, Exhibitor $exhibitor): void
    {
        if ($contact->exhibitor_id !== $exhibitor->id) {
            abort(403, 'You can only access your own exhibitor data.');
        }
    }
}
