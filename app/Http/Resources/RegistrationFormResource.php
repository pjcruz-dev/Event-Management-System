<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\RegistrationForm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RegistrationForm */
final class RegistrationFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'fields' => $this->fields,
        ];
    }
}
