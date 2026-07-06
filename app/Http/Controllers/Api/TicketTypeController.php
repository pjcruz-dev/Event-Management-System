<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketType\StoreTicketTypeRequest;
use App\Http\Requests\TicketType\UpdateTicketTypeRequest;
use App\Http\Resources\TicketTypeResource;
use App\Models\Event;
use App\Models\TicketType;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class TicketTypeController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $ticketTypes = $event->ticketTypes()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(TicketTypeResource::collection($ticketTypes));
    }

    public function store(StoreTicketTypeRequest $request, Event $event): JsonResponse
    {
        $this->authorize('create', TicketType::class);
        $this->authorize('update', $event);

        $ticketType = $event->ticketTypes()->create([
            ...$request->validated(),
            'organization_id' => $event->organization_id,
            'price' => $request->validated('price') ?? 0,
        ]);

        return ApiResponse::created(
            new TicketTypeResource($ticketType),
            'Ticket type created successfully.',
        );
    }

    public function update(
        UpdateTicketTypeRequest $request,
        Event $event,
        TicketType $ticketType,
    ): JsonResponse {
        $this->authorize('update', $ticketType);
        abort_unless($ticketType->event_id === $event->id, 404);

        $ticketType->update($request->validated());

        return ApiResponse::success(
            new TicketTypeResource($ticketType->fresh()),
            'Ticket type updated successfully.',
        );
    }

    public function destroy(Event $event, TicketType $ticketType): JsonResponse
    {
        $this->authorize('delete', $ticketType);
        abort_unless($ticketType->event_id === $event->id, 404);

        $ticketType->delete();

        return ApiResponse::success(message: 'Ticket type deleted successfully.');
    }
}
