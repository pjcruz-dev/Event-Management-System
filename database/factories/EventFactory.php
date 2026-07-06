<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Global Innovation Summit',
            'Future of Work Conference',
            'Design Leadership Forum',
            'Healthcare Transformation Expo',
            'Startup Founders Week',
            'Product Craft Festival',
        ]);

        $startsAt = fake()->dateTimeBetween('+1 month', '+8 months');
        $endsAt = (clone $startsAt)->modify('+'.fake()->numberBetween(1, 4).' days');

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'description' => fake()->paragraphs(3, true),
            'venue' => fake()->randomElement([
                'Moscone Center, San Francisco',
                'McCormick Place, Chicago',
                'Javits Center, New York',
                'Colorado Convention Center, Denver',
                'George R. Brown Convention Center, Houston',
            ]),
            'timezone' => fake()->timezone(),
            'capacity' => fake()->optional(0.7)->numberBetween(200, 5000),
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
            'category' => fake()->randomElement(EventCategory::cases())->value,
            'theme_config' => [
                'primary_color' => fake()->hexColor(),
                'secondary_color' => fake()->hexColor(),
                'font' => 'Inter',
                'logo_url' => null,
                'hero_image_url' => null,
                'layout_variant' => 'classic',
            ],
            'landing_page_config' => [
                'blocks' => [
                    ['type' => 'hero', 'settings' => (object) []],
                    ['type' => 'about', 'settings' => (object) []],
                    ['type' => 'agenda-preview', 'settings' => (object) []],
                ],
            ],
            'custom_domain' => null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => EventStatus::Published,
            'visibility' => EventVisibility::Public,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => EventStatus::Archived,
            'visibility' => EventVisibility::Private,
        ]);
    }
}
