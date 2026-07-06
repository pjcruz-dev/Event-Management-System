<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventTableShape;
use App\Models\Event;
use App\Models\EventTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTable>
 */
final class EventTableFactory extends Factory
{
    protected $model = EventTable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Event::factory(),
            'event_id' => Event::factory(),
            'name' => 'Table '.$this->faker->numberBetween(1, 50),
            'capacity' => $this->faker->numberBetween(4, 12),
            'sort_order' => 0,
            'shape' => EventTableShape::Round,
            'x' => null,
            'y' => null,
            'rotation' => null,
        ];
    }
}
