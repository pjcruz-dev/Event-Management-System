<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Review */
final class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $registration = $this->relationLoaded('registration') ? $this->registration : null;

        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'attendee_name' => $registration
                ? trim($registration->attendee_first_name.' '.$registration->attendee_last_name)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
