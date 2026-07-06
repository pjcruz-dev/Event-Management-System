<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Notifications\TicketIssuedNotification;
use App\Services\QrTokenService;
use App\Services\TicketPdfService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\AnonymousNotifiable;

final class RegistrationController extends Controller
{
    public function resendTicket(
        Event $event,
        Registration $registration,
        QrTokenService $qrTokenService,
        TicketPdfService $ticketPdfService,
    ): JsonResponse {
        $this->authorize('update', $event);
        $this->assertBelongsToEvent($event, $registration);

        if ($registration->status !== RegistrationStatus::Confirmed) {
            return ApiResponse::error('Only confirmed registrations can receive tickets.', null, 422);
        }

        if ($registration->qr_token_hash === null) {
            $token = $qrTokenService->generate($registration);
        } else {
            $token = $qrTokenService->resolveToken($registration);

            if ($token === null) {
                $token = $qrTokenService->generate($registration);
            }
        }

        $pdfPath = $ticketPdfService->generate($registration, $token);

        $registration->loadMissing('event');

        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $registration->attendee_email);
        $notifiable->notify(new TicketIssuedNotification($registration, $pdfPath));

        return ApiResponse::success(null, 'Ticket email queued for resend.');
    }

    public function cancel(Event $event, Registration $registration): JsonResponse
    {
        $this->authorize('update', $event);
        $this->assertBelongsToEvent($event, $registration);

        if ($registration->status === RegistrationStatus::Cancelled) {
            return ApiResponse::error('Registration is already cancelled.', null, 422);
        }

        $registration->update([
            'status' => RegistrationStatus::Cancelled,
            'qr_token_hash' => null,
            'qr_token_encrypted' => null,
        ]);

        event(new \App\Events\RegistrationCancelled($registration));

        return ApiResponse::success(null, 'Registration cancelled and QR code invalidated.');
    }

    private function assertBelongsToEvent(Event $event, Registration $registration): void
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }
    }
}
