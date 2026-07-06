<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'code' => strtoupper(Str::random(8)),
            'discount_type' => fake()->randomElement(CouponDiscountType::cases()),
            'discount_value' => fake()->randomElement([10, 15, 20, 25, 50, 100]),
            'max_uses' => fake()->optional()->numberBetween(10, 500),
            'times_used' => 0,
            'valid_from' => now(),
            'valid_until' => fake()->dateTimeBetween('+2 months', '+12 months'),
            'is_active' => true,
        ];
    }
}
