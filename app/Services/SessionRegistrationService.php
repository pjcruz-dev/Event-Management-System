<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Registration;
use App\Models\SessionRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SessionRegistrationService
{
    public function register(Event $event, EventSession $eventSession, Registration $registration): SessionRegistration
    {
        if ($eventSession->event_id !== $event->id) {
            abort(404);
        }

        if ($registration->event_id !== $event->id) {
            throw ValidationException::withMessages([
                'registration' => ['Registration does not belong to this event.'],
            ]);
        }

        if ($registration->status === RegistrationStatus::Cancelled
            || $registration->status === RegistrationStatus::Waitlisted) {
            throw ValidationException::withMessages([
                'registration' => ['This registration is not eligible for session signup.'],
            ]);
        }

        $registration->loadMissing('order');

        if ($registration->order === null || $registration->order->status !== OrderStatus::Paid) {
            throw ValidationException::withMessages([
                'registration' => ['Payment must be completed before session signup.'],
            ]);
        }

        if (! $eventSession->is_published) {
            throw ValidationException::withMessages([
                'event_session_id' => ['This session is not open for registration.'],
            ]);
        }

        return DB::transaction(function () use ($event, $eventSession, $registration): SessionRegistration {
            $locked = EventSession::query()->whereKey($eventSession->id)->lockForUpdate()->firstOrFail();

            if ($locked->capacity !== null) {
                $count = SessionRegistration::query()
                    ->where('event_session_id', $locked->id)
                    ->count();

                if ($count >= $locked->capacity) {
                    throw ValidationException::withMessages([
                        'event_session_id' => ['This session is at capacity.'],
                    ]);
                }
            }

            $existing = SessionRegistration::query()
                ->where('event_session_id', $locked->id)
                ->where('registration_id', $registration->id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return SessionRegistration::query()->create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'event_session_id' => $locked->id,
                'registration_id' => $registration->id,
            ]);
        });
    }

    public function registeredCount(EventSession $session): int
    {
        return SessionRegistration::query()
            ->where('event_session_id', $session->id)
            ->count();
    }
}
