<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $tax = round($subtotal * 0.08, 2);
        $total = round($subtotal + $tax, 2);

        return [
            'organization_id' => Organization::factory(),
            'order_id' => Order::factory(),
            'invoice_number' => 'INV-'.strtoupper((string) Str::ulid()),
            'status' => InvoiceStatus::Paid,
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'total' => $total,
            'currency' => 'USD',
            'issued_at' => now(),
            'due_at' => now()->addDays(14),
            'paid_at' => now(),
        ];
    }
}
