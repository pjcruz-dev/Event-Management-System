<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Exhibitor */
final class ExhibitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'description' => $this->description,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path
                ? app(StorageService::class)->url($this->logo_path, 'public')
                : null,
            'website_url' => $this->website_url,
            'contact_email' => $this->contact_email,
            'materials' => $this->materials ?? [],
            'booth' => new BoothResource($this->whenLoaded('booth')),
            'leads_count' => $this->whenCounted('leads'),
        ];
    }
}
