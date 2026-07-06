<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'actor_id' => User::factory(),
            'action' => fake()->randomElement([
                'event.created',
                'event.published',
                'registration.created',
                'order.paid',
                'member.invited',
            ]),
            'subject_type' => Event::class,
            'subject_id' => Event::factory(),
            'metadata' => [
                'ip' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
        ];
    }
}
