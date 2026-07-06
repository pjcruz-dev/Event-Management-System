<?php

declare(strict_types=1);

namespace App\Actions\Event;

use App\Actions\BaseAction;
use App\Models\Event;
use App\Models\EventPreviewToken;
use App\Models\User;

final class RotateEventPreviewTokenAction extends BaseAction
{
    public function handle(mixed ...$args): EventPreviewToken
    {
        /** @var Event $event */
        $event = $args[0];
        /** @var User $user */
        $user = $args[1];

        EventPreviewToken::query()
            ->where('event_id', $event->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return EventPreviewToken::create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays((int) config('event_builder.preview_token_ttl_days', 7)),
            'created_by' => $user->id,
        ]);
    }
}
