<?php

declare(strict_types=1);

namespace App\Actions\Event;

use App\Actions\BaseAction;
use App\Models\Event;
use App\Models\EventPreviewToken;

final class ValidateEventPreviewTokenAction extends BaseAction
{
    public function handle(mixed ...$args): EventPreviewToken
    {
        /** @var string $slug */
        $slug = $args[0];
        /** @var string $token */
        $token = $args[1];

        $previewToken = EventPreviewToken::withoutTenantScope('public event preview token')
            ->where('token', $token)
            ->first();

        if ($previewToken === null) {
            abort(404);
        }

        $event = Event::withoutTenantScope('public event preview token event')
            ->whereKey($previewToken->event_id)
            ->where('slug', $slug)
            ->first();

        if ($event === null) {
            abort(404);
        }

        if ($previewToken->organization_id !== $event->organization_id) {
            abort(404);
        }

        if (! $previewToken->isActive()) {
            abort(404);
        }

        if (
            $previewToken->last_used_at === null
            || $previewToken->last_used_at->lt(now()->subMinutes(5))
        ) {
            $previewToken->update(['last_used_at' => now()]);
        }

        $previewToken->setRelation('event', $event);

        return $previewToken;
    }
}
