<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    protected $model = Track::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'name' => fake()->randomElement([
                'Main Stage',
                'Product Track',
                'Engineering Track',
                'Leadership Track',
                'Workshops',
            ]),
            'description' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
