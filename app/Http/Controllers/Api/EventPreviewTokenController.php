<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Event\RevokeEventPreviewTokenAction;
use App\Actions\Event\RotateEventPreviewTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventPreviewTokenResource;
use App\Models\Event;
use App\Models\EventPreviewToken;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EventPreviewTokenController extends Controller
{
    public function show(Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $token = EventPreviewToken::query()
            ->where('event_id', $event->id)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->with('event')
            ->first();

        if ($token === null) {
            return ApiResponse::success(null, 'No active preview token.');
        }

        return ApiResponse::success(new EventPreviewTokenResource($token));
    }

    public function store(
        Request $request,
        Event $event,
        RotateEventPreviewTokenAction $rotate,
    ): JsonResponse {
        $this->authorize('update', $event);

        $token = $rotate->handle($event, $request->user());
        $token->load('event');

        return ApiResponse::created(
            new EventPreviewTokenResource($token),
            'Preview token created.',
        );
    }

    public function destroy(Event $event, RevokeEventPreviewTokenAction $revoke): JsonResponse
    {
        $this->authorize('update', $event);

        $revoke->handle($event);

        return ApiResponse::success(null, 'Preview token revoked.');
    }
}
