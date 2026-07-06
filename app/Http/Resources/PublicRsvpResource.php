<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\GuestInvite;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GuestInvite */
final class PublicRsvpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event;
        $settings = $event->resolvedRsvpSettings();

        return [
            'invite' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'status' => $this->status?->value,
                'rsvp_response' => $this->rsvp_response?->value,
                'responded_at' => $this->responded_at?->toIso8601String(),
                'table_name' => $this->table?->name,
            ],
            'event' => [
                'name' => $event->name,
                'slug' => $event->slug,
                'venue' => $event->venue,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'timezone' => $event->timezone,
                'registration_mode' => $event->registration_mode?->value,
            ],
            'rsvp_settings' => [
                'allow_plus_ones' => (bool) ($settings['allow_plus_ones'] ?? false),
                'max_plus_ones_per_invite' => (int) ($settings['max_plus_ones_per_invite'] ?? 0),
                'collect_meal_preferences' => (bool) ($settings['collect_meal_preferences'] ?? false),
                'meal_options' => $settings['meal_options'] ?? ['Chicken', 'Fish', 'Vegetarian', 'Vegan'],
                'allow_maybe_response' => (bool) ($settings['allow_maybe_response'] ?? true),
                'response_deadline' => $settings['response_deadline'] ?? null,
            ],
            'ticket_types' => TicketTypeResource::collection(
                TicketType::withoutTenantScope('public rsvp tickets')
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(),
            ),
        ];
    }
}
