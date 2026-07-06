<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'user_id' => fake()->optional(0.6)->passthrough(User::factory()),
            'ticket_type_id' => TicketType::factory(),
            'registration_number' => 'REG-'.strtoupper((string) Str::ulid()),
            'status' => RegistrationStatus::Confirmed,
            'attendee_first_name' => fake()->firstName(),
            'attendee_last_name' => fake()->lastName(),
            'attendee_email' => fake()->unique()->safeEmail(),
            'attendee_phone' => fake()->optional()->phoneNumber(),
            'custom_fields' => [
                'company' => fake()->optional()->company(),
                'job_title' => fake()->optional()->jobTitle(),
            ],
            'checked_in_at' => null,
        ];
    }
}
