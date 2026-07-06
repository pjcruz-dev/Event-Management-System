<?php

declare(strict_types=1);

namespace App\Actions\Event;

use App\Actions\BaseAction;
use App\Models\Event;
use App\Models\EventPreviewToken;

final class RevokeEventPreviewTokenAction extends BaseAction
{
    public function handle(mixed ...$args): null
    {
        /** @var Event $event */
        $event = $args[0];

        EventPreviewToken::query()
            ->where('event_id', $event->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return null;
    }
}
