<?php

declare(strict_types=1);

namespace App\Actions\Event;

use App\Actions\BaseAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organization;
use App\Services\EventSlugService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublishEventAction extends BaseAction
{
    public function handle(mixed ...$args): Event
    {
        /** @var Event $event */
        $event = $args[0];

        $this->assertCanPublish($event);

        $event->update([
            'status' => EventStatus::Published,
            'visibility' => $event->visibility === EventVisibility::Private
                ? EventVisibility::Public
                : $event->visibility,
            'published_at' => now(),
        ]);

        return $event->fresh();
    }

    private function assertCanPublish(Event $event): void
    {
        $errors = [];

        if (trim((string) $event->name) === '') {
            $errors['name'] = ['Event name is required before publishing.'];
        }

        if ($event->starts_at === null || $event->ends_at === null) {
            $errors['starts_at'] = ['Event start and end dates are required before publishing.'];
        }

        if (trim((string) $event->venue) === '') {
            $errors['venue'] = ['Event venue is required before publishing.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
