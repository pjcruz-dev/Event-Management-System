<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organization;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSession>
 */
class EventSessionFactory extends Factory
{
    protected $model = EventSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 month', '+6 months');
        $endsAt = (clone $startsAt)->modify('+'.fake()->numberBetween(30, 90).' minutes');

        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'track_id' => Track::factory(),
            'title' => fake()->randomElement([
                'Opening Keynote: The Next Decade',
                'Building Resilient Teams',
                'AI in Production',
                'Design Systems at Scale',
                'Fireside Chat with Industry Leaders',
            ]),
            'description' => fake()->paragraph(),
            'room' => fake()->randomElement(['Hall A', 'Room 201', 'Auditorium', 'Studio B', 'Expo Theater']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => fake()->optional()->numberBetween(50, 500),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
