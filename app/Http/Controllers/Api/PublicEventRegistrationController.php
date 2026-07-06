<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Registration\CreateRegistrationAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\TicketTypeVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\ApplyCouponRequest;
use App\Http\Requests\Registration\PublicRegisterRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\RegistrationCheckoutResource;
use App\Http\Resources\RegistrationFormResource;
use App\Http\Resources\TicketTypeResource;
use App\Models\Event;
use App\Models\RegistrationForm;
use App\Models\TicketType;
use App\Services\CouponService;
use App\Services\GuestInviteAccessService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PublicEventRegistrationController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse
    {
        $event = $this->findPublishedEvent($slug);

        $invitationToken = $request->query('invitation_token');
        $tokenValid = false;

        if ($event->requiresInvitationToken() && $invitationToken) {
            try {
                app(GuestInviteAccessService::class)->validateForPublicRegistration($event, (string) $invitationToken);
                $tokenValid = true;
            } catch (\Illuminate\Validation\ValidationException) {
                $tokenValid = false;
            }
        }

        $includeTicketId = $request->filled('ticket')
            ? $request->integer('ticket')
            : null;

        $ticketTypes = collect();

        if (! $event->requiresInvitationToken() || $tokenValid) {
            $ticketTypes = TicketType::withoutTenantScope('public ticket listing')
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where(function ($query) use ($includeTicketId): void {
                    $query->where('visibility', TicketTypeVisibility::Public);

                    if ($includeTicketId !== null) {
                        $query->orWhere('id', $includeTicketId);
                    }
                })
                ->orderBy('sort_order')
                ->get();
        }

        return ApiResponse::success([
            'event' => new EventResource($event),
            'ticket_types' => TicketTypeResource::collection($ticketTypes),
        ]);
    }

    public function registrationForm(string $slug): JsonResponse
    {
        $event = $this->findPublishedEvent($slug);

        $form = RegistrationForm::withoutTenantScope('public registration form')
            ->where('event_id', $event->id)
            ->first();

        if ($form === null) {
            return ApiResponse::success([
                'fields' => [],
            ]);
        }

        return ApiResponse::success(new RegistrationFormResource($form));
    }

    public function applyCoupon(ApplyCouponRequest $request, string $slug, CouponService $couponService): JsonResponse
    {
        $event = $this->findPublishedEvent($slug);

        $ticketType = TicketType::withoutTenantScope('public coupon apply')
            ->where('event_id', $event->id)
            ->whereKey($request->validated('ticket_type_id'))
            ->firstOrFail();

        $quantity = (int) $request->validated('quantity');

        $result = $couponService->apply($event, $request->validated('code'), [[
            'ticket_type_id' => $ticketType->id,
            'quantity' => $quantity,
            'unit_price' => (float) $ticketType->price,
        ]]);

        return ApiResponse::success([
            'subtotal' => $result['subtotal'],
            'discount_total' => $result['discount_total'],
            'total' => $result['total'],
            'currency' => $ticketType->currency,
        ]);
    }

    public function register(
        PublicRegisterRequest $request,
        string $slug,
        CreateRegistrationAction $action,
        GuestInviteAccessService $inviteAccess,
        \App\Services\TenantContext $tenantContext,
    ): JsonResponse {
        $event = $this->findPublishedEvent($slug);

        if (! $tenantContext->isResolved()) {
            $tenantContext->set($event->organization);
        }

        $inviteAccess->validateForPublicRegistration(
            $event,
            $request->query('invitation_token'),
        );

        $result = $action->handle(
            $event,
            $request->validated(),
            $request->user(),
        );

        $status = $result->isWaitingList() ? 202 : 201;
        $message = $result->isWaitingList()
            ? 'Added to the waiting list.'
            : ($result->order?->status === \App\Enums\OrderStatus::Paid
                ? 'Registration confirmed.'
                : 'Registration created. Payment pending.');

        return ApiResponse::success(
            new RegistrationCheckoutResource($result),
            $message,
            $status,
        );
    }

    private function findPublishedEvent(string $slug): Event
    {
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
}
