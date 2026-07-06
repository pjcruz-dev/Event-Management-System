<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->randomElement(['brochure.pdf', 'logo.png', 'schedule.pdf']);

        return [
            'organization_id' => Organization::factory(),
            'attachable_type' => Event::class,
            'attachable_id' => Event::factory(),
            'disk' => 'local',
            'path' => 'attachments/'.fake()->uuid().'/'.$filename,
            'filename' => $filename,
            'mime_type' => str_ends_with($filename, '.pdf') ? 'application/pdf' : 'image/png',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
        ];
    }
}
