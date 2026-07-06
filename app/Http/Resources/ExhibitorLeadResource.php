<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExhibitorLead */
final class ExhibitorLeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $registration = $this->relationLoaded('registration') ? $this->registration : null;

        return [
            'id' => $this->id,
            'scanned_at' => $this->scanned_at?->toIso8601String(),
            'notes' => $this->notes,
            'attendee_name' => $registration
                ? trim($registration->attendee_first_name.' '.$registration->attendee_last_name)
                : null,
            'attendee_email' => $registration?->attendee_email,
            'registration_number' => $registration?->registration_number,
        ];
    }
}
