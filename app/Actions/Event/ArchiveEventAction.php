<?php

declare(strict_types=1);

namespace App\Actions\Event;

use App\Actions\BaseAction;
use App\Enums\EventStatus;
use App\Models\Event;

final class ArchiveEventAction extends BaseAction
{
    public function handle(mixed ...$args): Event
    {
        /** @var Event $event */
        $event = $args[0];

        $event->update([
            'status' => EventStatus::Archived,
        ]);

        return $event->fresh();
    }
}
