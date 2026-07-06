<?php

declare(strict_types=1);

namespace App\Actions\Registration;

use App\Enums\RegistrationStatus;
use App\Enums\WaitingListStatus;
use App\Events\RegistrationConfirmed;
use App\Models\Registration;
use App\Models\WaitingListEntry;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PromoteWaitingListEntryAction
{
    public function handle(WaitingListEntry $entry): Registration
    {
        if ($entry->status !== WaitingListStatus::Waiting) {
            throw ValidationException::withMessages([
                'waiting_list' => ['This entry has already been processed.'],
            ]);
        }

        $registration = Registration::query()->create([
            'organization_id' => $entry->organization_id,
            'event_id' => $entry->event_id,
            'user_id' => $entry->user_id,
            'ticket_type_id' => $entry->ticket_type_id,
            'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => $entry->attendee_first_name,
            'attendee_last_name' => $entry->attendee_last_name,
            'attendee_email' => $entry->attendee_email,
            'attendee_phone' => $entry->attendee_phone,
            'custom_fields' => $entry->custom_fields ?? [],
        ]);

        $entry->update(['status' => WaitingListStatus::Promoted]);

        event(new RegistrationConfirmed($registration));

        return $registration;
    }
}
