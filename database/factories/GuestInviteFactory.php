<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GuestInviteStatus;
use App\Models\Event;
use App\Models\GuestInvite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GuestInvite>
 */
final class GuestInviteFactory extends Factory
{
    protected $model = GuestInvite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Event::factory(),
            'event_id' => Event::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => null,
            'invitation_token' => Str::random(64),
            'status' => GuestInviteStatus::Pending,
            'rsvp_response' => null,
            'household_name' => null,
            'group_label' => null,
            'tags' => [],
            'plus_one_limit' => null,
            'table_id' => null,
            'registration_id' => null,
            'sent_at' => null,
            'opened_at' => null,
            'responded_at' => null,
        ];
    }
}
