<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'registration_id' => Registration::factory(),
            'certificate_number' => 'CERT-'.strtoupper((string) Str::ulid()),
            'issued_at' => now(),
            'template_config' => [
                'title' => 'Certificate of Attendance',
                'subtitle' => 'This certifies successful participation',
                'signature_name' => fake()->name(),
                'background_url' => null,
            ],
            'file_path' => null,
        ];
    }
}
