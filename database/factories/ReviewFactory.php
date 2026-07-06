<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'registration_id' => Registration::factory(),
            'user_id' => fake()->optional(0.7)->passthrough(User::factory()),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->optional(0.8)->paragraph(),
            'is_published' => fake()->boolean(70),
        ];
    }
}
