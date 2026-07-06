<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSetting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSetting>
 */
class EventSettingFactory extends Factory
{
    protected $model = EventSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'key' => fake()->randomElement(['registration_form', 'check_in', 'email_branding']),
            'value' => ['enabled' => true],
        ];
    }
}
