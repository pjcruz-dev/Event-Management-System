<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventTableResource;
use App\Http\Resources\GuestInviteResource;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Models\Registration;
use App\Services\SeatingAssignmentService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EventSeatingController extends Controller
{
    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $tables = $event->eventTables()
            ->with(['guestInvites' => fn ($q) => $q->with('registration'), 'registrations.ticketType'])
            ->orderBy('sort_order')
            ->get();

        $unassignedInvites = GuestInvite::query()
            ->where('event_id', $event->id)
            ->whereNull('table_id')
            ->whereNull('registration_id')
            ->orderBy('last_name')
            ->get();

        $unassignedRegistrations = Registration::query()
            ->where('event_id', $event->id)
            ->whereNull('table_id')
            ->where('is_plus_one', false)
            ->with('ticketType')
            ->orderBy('attendee_last_name')
            ->get();

        return ApiResponse::success([
            'tables' => EventTableResource::collection($tables),
            'unassigned' => [
                'guest_invites' => GuestInviteResource::collection($unassignedInvites),
                'registrations' => $unassignedRegistrations->map(fn (Registration $r) => [
                    'id' => $r->id,
                    'registration_number' => $r->registration_number,
                    'attendee_name' => trim($r->attendee_first_name.' '.$r->attendee_last_name),
                    'attendee_email' => $r->attendee_email,
                    'ticket_type' => $r->ticketType?->name,
                ])->values(),
            ],
        ]);
    }

    public function assign(Request $request, Event $event, SeatingAssignmentService $service): JsonResponse
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'registrations' => ['nullable', 'array'],
            'registrations.*.id' => ['required', 'integer'],
            'registrations.*.table_id' => ['nullable', 'integer'],
            'guest_invites' => ['nullable', 'array'],
            'guest_invites.*.id' => ['required', 'integer'],
            'guest_invites.*.table_id' => ['nullable', 'integer'],
        ]);

        $service->bulkAssign($event, $data);

        return ApiResponse::success(null, 'Seating assignments updated.');
    }
}
