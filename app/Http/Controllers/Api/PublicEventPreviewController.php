<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Event\ValidateEventPreviewTokenAction;
use App\Enums\TicketTypeVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\TicketTypeResource;
use App\Models\TicketType;
use App\Services\TenantContext;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PublicEventPreviewController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function show(Request $request, string $slug, ValidateEventPreviewTokenAction $validate): JsonResponse
    {
        $token = $request->query('token');

        if (! is_string($token) || $token === '') {
            abort(404);
        }

        $previewToken = $validate->handle($slug, $token);
        $event = $previewToken->event;

        $this->tenantContext->set($event->organization);

        $ticketTypes = TicketType::withoutTenantScope('public preview ticket listing')
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->where('visibility', TicketTypeVisibility::Public)
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success([
            'event' => new EventResource($event),
            'ticket_types' => TicketTypeResource::collection($ticketTypes),
            'preview' => true,
        ]);
    }
}
