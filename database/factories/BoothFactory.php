<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booth;
use App\Models\Event;
use App\Models\Exhibitor;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booth>
 */
class BoothFactory extends Factory
{
    protected $model = Booth::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'exhibitor_id' => Exhibitor::factory(),
            'code' => strtoupper(fake()->bothify('?##')),
            'location' => fake()->randomElement(['Hall 1', 'Hall 2', 'Expo Floor A', 'Expo Floor B']),
        ];
    }
}
