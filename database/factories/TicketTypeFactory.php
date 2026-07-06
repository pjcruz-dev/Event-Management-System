<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TicketTypeVisibility;
use App\Models\Event;
use App\Models\Organization;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomElement([0, 49.00, 99.00, 149.00, 299.00, 499.00]);

        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'name' => fake()->randomElement([
                'General Admission',
                'VIP Pass',
                'Early Bird',
                'Student Ticket',
                'Workshop Add-on',
            ]),
            'description' => fake()->optional()->sentence(),
            'price' => $price,
            'currency' => 'USD',
            'quantity' => fake()->optional(0.8)->numberBetween(50, 1000),
            'quantity_sold' => 0,
            'sales_starts_at' => now(),
            'sales_ends_at' => fake()->dateTimeBetween('+1 month', '+10 months'),
            'visibility' => TicketTypeVisibility::Public,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Free Registration',
            'price' => 0,
        ]);
    }
}
