<?php

declare(strict_types=1);

namespace App\Actions\Event;

use App\Actions\BaseAction;
use App\Enums\CustomDomainVerificationStatus;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Services\EventSlugService;
use Illuminate\Support\Facades\DB;

final class DuplicateEventAction extends BaseAction
{
    public function __construct(
        private readonly EventSlugService $eventSlugService,
    ) {}

    public function handle(mixed ...$args): Event
    {
        /** @var Event $event */
        $event = $args[0];

        return DB::transaction(function () use ($event): Event {
            $organization = $event->organization;
            $copyName = $event->name.' (Copy)';
            $slug = $this->eventSlugService->generateUnique($organization, $copyName);

            return Event::query()->create([
                'organization_id' => $event->organization_id,
                'name' => $copyName,
                'slug' => $slug,
                'description' => $event->description,
                'venue' => $event->venue,
                'timezone' => $event->timezone,
                'capacity' => $event->capacity,
                'status' => EventStatus::Draft,
                'visibility' => EventVisibility::Private,
                'theme_config' => $event->theme_config,
                'landing_page_config' => $event->landing_page_config,
                'custom_domain' => null,
                'custom_domain_verification_status' => CustomDomainVerificationStatus::Unverified,
                'meta_title' => $event->meta_title,
                'meta_description' => $event->meta_description,
                'og_image_path' => $event->og_image_path,
                'starts_at' => null,
                'ends_at' => null,
                'published_at' => null,
            ]);
        });
    }
}
