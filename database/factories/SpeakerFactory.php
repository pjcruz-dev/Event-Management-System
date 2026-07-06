<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Speaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Speaker>
 */
class SpeakerFactory extends Factory
{
    protected $model = Speaker::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'name' => fake()->name(),
            'title' => fake()->jobTitle(),
            'bio' => fake()->paragraphs(2, true),
            'photo_path' => null,
            'social_links' => [
                'twitter' => 'https://twitter.com/'.fake()->userName(),
                'linkedin' => 'https://linkedin.com/in/'.fake()->userName(),
                'website' => fake()->url(),
            ],
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
