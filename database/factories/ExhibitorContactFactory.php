<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Exhibitor;
use App\Models\ExhibitorContact;
use App\Models\Organization;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<ExhibitorContact>
 */
class ExhibitorContactFactory extends Factory
{
    protected $model = ExhibitorContact::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'exhibitor_id' => Exhibitor::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ];
    }
}
