<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventTableShape;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventTableResource;
use App\Models\Event;
use App\Models\EventTable;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class EventTableController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', EventTable::class);

        $tables = $event->eventTables()
            ->withCount(['guestInvites', 'registrations'])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(EventTableResource::collection($tables));
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('create', EventTable::class);
        $this->authorize('update', $event);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'shape' => ['nullable', Rule::enum(EventTableShape::class)],
            'x' => ['nullable', 'numeric'],
            'y' => ['nullable', 'numeric'],
            'rotation' => ['nullable', 'numeric'],
        ]);

        $table = $event->eventTables()->create([
            'organization_id' => $event->organization_id,
            'name' => $data['name'],
            'capacity' => $data['capacity'],
            'sort_order' => $data['sort_order'] ?? 0,
            'shape' => $data['shape'] ?? EventTableShape::Round,
            'x' => $data['x'] ?? null,
            'y' => $data['y'] ?? null,
            'rotation' => $data['rotation'] ?? null,
        ]);

        return ApiResponse::success(new EventTableResource($table), 'Table created.', 201);
    }

    public function update(Request $request, Event $event, EventTable $eventTable): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $eventTable);
        $this->authorize('update', $eventTable);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'shape' => ['nullable', Rule::enum(EventTableShape::class)],
            'x' => ['nullable', 'numeric'],
            'y' => ['nullable', 'numeric'],
            'rotation' => ['nullable', 'numeric'],
        ]);

        $eventTable->update($data);

        return ApiResponse::success(new EventTableResource($eventTable->fresh()));
    }

    public function destroy(Event $event, EventTable $eventTable): JsonResponse
    {
        $this->ensureBelongsToEvent($event, $eventTable);
        $this->authorize('delete', $eventTable);

        $eventTable->delete();

        return ApiResponse::success(null, 'Table deleted.');
    }

    private function ensureBelongsToEvent(Event $event, EventTable $eventTable): void
    {
        if ($eventTable->event_id !== $event->id) {
            abort(404);
        }
    }
}
