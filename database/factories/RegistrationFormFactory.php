<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use App\Models\RegistrationForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationForm>
 */
class RegistrationFormFactory extends Factory
{
    protected $model = RegistrationForm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'fields' => [
                [
                    'key' => 'company',
                    'type' => 'text',
                    'label' => 'Company',
                    'required' => true,
                    'options' => [],
                ],
            ],
        ];
    }
}
