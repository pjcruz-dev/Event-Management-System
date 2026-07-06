<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Rsvp\RespondToGuestInviteAction;
use App\DTO\Registration\RegistrationCheckoutResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuestInvite\PublicRsvpRespondRequest;
use App\Http\Resources\GuestInviteResource;
use App\Http\Resources\PublicRsvpResource;
use App\Http\Resources\RegistrationCheckoutResource;
use App\Models\GuestInvite;
use App\Services\GuestInviteAccessService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PublicRsvpController extends Controller
{
    public function show(string $token, GuestInviteAccessService $accessService): JsonResponse
    {
        $invite = $accessService->resolveForPublicAccess($token);
        $accessService->markOpened($invite);

        return ApiResponse::success(new PublicRsvpResource($invite));
    }

    public function respond(
        PublicRsvpRespondRequest $request,
        string $token,
        GuestInviteAccessService $accessService,
        RespondToGuestInviteAction $action,
        \App\Services\TenantContext $tenantContext,
    ): JsonResponse {
        $invite = $accessService->resolveForPublicAccess($token);

        if (! $tenantContext->isResolved()) {
            $event = $invite->event()->withoutGlobalScopes()->first();
            if ($event?->organization !== null) {
                $tenantContext->set($event->organization);
            }
        }

        $result = $action->handle($invite, $request->validated());

        if ($result instanceof RegistrationCheckoutResult) {
            $message = $result->isWaitingList()
                ? 'Added to the waiting list.'
                : 'RSVP accepted.';

            return ApiResponse::success(
                new RegistrationCheckoutResource($result),
                $message,
                $result->isWaitingList() ? 202 : 201,
            );
        }

        return ApiResponse::success(
            new GuestInviteResource($result),
            'RSVP recorded.',
        );
    }
}
