<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Event;
use App\Models\GuestInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RsvpReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly GuestInvite $invite,
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
        $event = Event::withoutTenantScope('rsvp reminder mail')
            ->findOrFail($this->invite->event_id);
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $rsvpUrl = $frontendUrl.'/rsvp/'.$this->invite->invitation_token;

        return (new MailMessage)
            ->subject('Reminder: RSVP for '.$event->name)
            ->greeting('Hello '.$this->invite->first_name.'!')
            ->line('This is a friendly reminder to respond to your invitation for **'.$event->name.'**.')
            ->action('Respond now', $rsvpUrl);
    }
}
