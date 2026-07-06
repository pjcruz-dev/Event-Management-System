<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GuestInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GuestInvite */
final class GuestInviteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'status' => $this->status?->value,
            'rsvp_response' => $this->rsvp_response?->value,
            'household_name' => $this->household_name,
            'group_label' => $this->group_label,
            'tags' => $this->tags ?? [],
            'plus_one_limit' => $this->plus_one_limit,
            'table_id' => $this->table_id,
            'table_name' => $this->whenLoaded('table', fn () => $this->table?->name),
            'registration_id' => $this->registration_id,
            'registration_number' => $this->whenLoaded('registration', fn () => $this->registration?->registration_number),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'last_reminder_sent_at' => $this->last_reminder_sent_at?->toIso8601String(),
            'reminder_count' => $this->reminder_count ?? 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
