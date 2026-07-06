<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Support\Str;

final class EventSlugService
{
    public function generateUnique(Organization $organization, string $name, ?string $slug = null): string
    {
        $base = Str::slug($slug ?? $name);

        if ($base === '') {
            $base = 'event';
        }

        $candidate = $base;
        $counter = 1;

        while ($this->slugExists($organization, $candidate)) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function slugExists(Organization $organization, string $slug): bool
    {
        return Event::withoutTenantScope('slug uniqueness check')
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->exists();
    }
}
