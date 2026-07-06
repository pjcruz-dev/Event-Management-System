<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SponsorTier;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'name' => fake()->company(),
            'logo_path' => null,
            'website_url' => fake()->url(),
            'tier' => fake()->randomElement(SponsorTier::cases()),
            'description' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
