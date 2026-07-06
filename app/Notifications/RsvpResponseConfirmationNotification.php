<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\RsvpResponse;
use App\Models\Event;
use App\Models\GuestInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RsvpResponseConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly GuestInvite $invite,
        private readonly RsvpResponse $response,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = Event::withoutTenantScope('rsvp confirmation mail')
            ->findOrFail($this->invite->event_id);

        $confirmationSettings = $event->resolvedConfirmationSettings();

        $subject = match ($this->response) {
            RsvpResponse::Accepted => 'RSVP confirmed for '.$event->name,
            RsvpResponse::Declined => 'RSVP recorded for '.$event->name,
            RsvpResponse::Maybe => 'RSVP recorded for '.$event->name,
        };

        $customKey = match ($this->response) {
            RsvpResponse::Accepted => 'rsvp_accepted_message',
            RsvpResponse::Declined => 'rsvp_declined_message',
            RsvpResponse::Maybe => 'rsvp_maybe_message',
        };

        $defaultLine = match ($this->response) {
            RsvpResponse::Accepted => 'Your attendance has been confirmed.',
            RsvpResponse::Declined => 'We have recorded that you are unable to attend.',
            RsvpResponse::Maybe => 'We have recorded your tentative response.',
        };

        $line = ! empty($confirmationSettings[$customKey])
            ? $confirmationSettings[$customKey]
            : $defaultLine;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$this->invite->first_name.'!')
            ->line($line)
            ->line('Event: **'.$event->name.'**');
    }
}
