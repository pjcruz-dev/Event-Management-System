<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiscoverEventResource;
use App\Http\Resources\ReviewResource;
use App\Http\Requests\Public\StorePublicReviewRequest;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Review;
use App\Models\Scopes\TenantScope;
use App\Services\DiscoverEventService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class PublicReviewController extends Controller
{
    public function index(string $slug): JsonResponse
    {
        $event = $this->findPublishedEvent($slug);

        $reviews = Review::withoutTenantScope('public reviews read')
            ->where('event_id', $event->id)
            ->where('is_published', true)
            ->with(['registration' => fn ($query) => $query->withoutGlobalScope(TenantScope::class)])
            ->latest()
            ->paginate(20);

        return ApiResponse::paginated(
            $reviews->through(fn ($review) => new ReviewResource($review)),
        );
    }

    public function store(StorePublicReviewRequest $request, string $slug): JsonResponse
    {
        $event = $this->findPublishedEvent($slug);

        if ($event->ends_at === null || $event->ends_at->isFuture()) {
            throw ValidationException::withMessages([
                'event' => ['Reviews are only accepted after the event has ended.'],
            ]);
        }

        $data = $request->validated();

        $registration = Registration::withoutTenantScope('public review submit')
            ->where('event_id', $event->id)
            ->where('registration_number', $data['registration_number'])
            ->where('attendee_email', $data['attendee_email'])
            ->first();

        if ($registration === null) {
            throw ValidationException::withMessages([
                'registration_number' => ['Registration not found for this event.'],
            ]);
        }

        if (Review::withoutTenantScope('public review duplicate')
            ->where('registration_id', $registration->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'registration_number' => ['A review has already been submitted for this registration.'],
            ]);
        }

        $review = Review::withoutTenantScope('public review create')->create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'user_id' => $registration->user_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_published' => true,
        ]);

        $review->setRelation('registration', $registration);

        return ApiResponse::created(new ReviewResource($review), 'Thank you for your review.');
    }

    public function similar(string $slug, DiscoverEventService $discoverService): JsonResponse
    {
        $event = $this->findPublishedEvent($slug);

        return ApiResponse::success(
            DiscoverEventResource::collection($discoverService->similarEvents($event)),
        );
    }

    private function findPublishedEvent(string $slug): Event
    {
        $event = Event::withoutTenantScope('public event lookup')
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->where('visibility', EventVisibility::Public)
            ->first();

        if ($event === null) {
            abort(404);
        }

        return $event;
    }
}
