<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 0, 500);
        $discount = fake()->randomFloat(2, 0, min(50, $subtotal));
        $tax = round(($subtotal - $discount) * 0.08, 2);
        $total = round($subtotal - $discount + $tax, 2);

        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'user_id' => fake()->optional(0.6)->passthrough(User::factory()),
            'registration_id' => Registration::factory(),
            'order_number' => 'ORD-'.strtoupper((string) Str::ulid()),
            'status' => OrderStatus::Paid,
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_total' => $tax,
            'total' => $total,
            'currency' => 'USD',
            'coupon_id' => null,
            'paid_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Pending,
            'paid_at' => null,
        ]);
    }
}
