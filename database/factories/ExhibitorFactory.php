<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Exhibitor;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exhibitor>
 */
class ExhibitorFactory extends Factory
{
    protected $model = Exhibitor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'logo_path' => null,
            'website_url' => fake()->url(),
            'contact_email' => fake()->companyEmail(),
        ];
    }
}
