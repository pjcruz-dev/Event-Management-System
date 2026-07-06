<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 0, 500);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'organization_id' => Organization::factory(),
            'order_id' => Order::factory(),
            'ticket_type_id' => TicketType::factory(),
            'registration_id' => Registration::factory(),
            'description' => fake()->randomElement([
                'General Admission',
                'VIP Pass',
                'Workshop Add-on',
            ]),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $quantity, 2),
        ];
    }
}
