<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Registration\CreateWalkInRegistrationAction;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalkInController extends Controller
{
    public function store(
        Request $request,
        Event $event,
        CreateWalkInRegistrationAction $action,
    ): JsonResponse {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gate' => ['nullable', 'string', 'max:255'],
            'ticket_type_id' => ['nullable', 'integer', 'exists:ticket_types,id'],
            'payment_collected' => ['nullable', 'boolean'],
        ]);

        $registration = $action->handle($event, $validated, $request->user());

        return ApiResponse::created([
            'registration' => [
                'id' => $registration->id,
                'registration_number' => $registration->registration_number,
                'attendee_name' => trim($registration->attendee_first_name.' '.$registration->attendee_last_name),
                'attendee_email' => $registration->attendee_email,
                'checked_in_at' => $registration->checked_in_at?->toIso8601String(),
            ],
        ], 'Walk-in registered and checked in.');
    }
}
