<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Summit Events Co',
            'Harbor Conference Group',
            'Pulse Live Experiences',
            'Northstar Gatherings',
            'Civic Hall Productions',
        ]).' '.fake()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'logo_path' => null,
            'owner_id' => User::factory(),
            'settings' => [],
        ];
    }
}
