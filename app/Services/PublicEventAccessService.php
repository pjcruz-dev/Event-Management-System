<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Event\ValidateEventPreviewTokenAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;

final class PublicEventAccessService
{
    public function __construct(
        private readonly ValidateEventPreviewTokenAction $validatePreviewToken,
    ) {}

    public function resolveBySlug(string $slug, ?string $previewToken = null): Event
    {
        if ($previewToken !== null && $previewToken !== '') {
            return $this->validatePreviewToken->handle($slug, $previewToken)->event;
        }

        $event = Event::withoutTenantScope('public event by slug')
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->where('visibility', EventVisibility::Public)
            ->first();

        if ($event === null) {
            abort(404);
        }

        return $event;
    }

    public function extractPreviewToken(?string $previewToken, ?string $token): ?string
    {
        $value = $previewToken ?? $token;

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
