<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTO\CheckIn\CheckInScanResult;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CheckInScanResult */
final class CheckInScanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CheckInScanResult $result */
        $result = $this->resource;

        return [
            'status' => $result->status,
            'reason' => $result->reason,
            'is_vip' => $result->isVip,
            'duplicate_info' => $result->duplicateInfo,
            'registration' => $result->registration !== null
                ? $this->registrationPayload($result->registration)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(Registration $registration): array
    {
        $registration->loadMissing(['ticketType', 'checkedInBy', 'table']);

        return [
            'id' => $registration->id,
            'registration_number' => $registration->registration_number,
            'attendee_name' => trim($registration->attendee_first_name.' '.$registration->attendee_last_name),
            'attendee_email' => $registration->attendee_email,
            'ticket_type' => $registration->ticketType?->name,
            'table_name' => $registration->table?->name,
            'checked_in_at' => $registration->checked_in_at?->toIso8601String(),
            'checked_in_by' => $registration->checkedInBy?->name,
            'gate' => $registration->check_in_gate,
        ];
    }
}

