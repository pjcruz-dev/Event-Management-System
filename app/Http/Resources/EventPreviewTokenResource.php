<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EventPreviewToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventPreviewToken */
final class EventPreviewTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event;

        return [
            'token' => $this->token,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'preview_path' => $event
                ? '/e/'.$event->slug.'/preview?token='.urlencode($this->token)
                : null,
        ];
    }
}
