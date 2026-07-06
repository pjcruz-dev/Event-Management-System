<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Event;
use App\Models\GuestInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class GuestInvitationNotification extends Notification implements ShouldQueue
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
        $event = Event::withoutTenantScope('guest invitation mail')
            ->findOrFail($this->invite->event_id);
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $rsvpUrl = $frontendUrl.'/rsvp/'.$this->invite->invitation_token;

        return (new MailMessage)
            ->subject('You are invited to '.$event->name)
            ->greeting('Hello '.$this->invite->first_name.'!')
            ->line('You have been invited to **'.$event->name.'**.')
            ->when($event->starts_at !== null, fn (MailMessage $message) => $message
                ->line('Date: '.$event->starts_at->timezone($event->timezone)->toDayDateTimeString()))
            ->when($event->venue !== null, fn (MailMessage $message) => $message
                ->line('Venue: '.$event->venue))
            ->action('Respond to invitation', $rsvpUrl)
            ->line('We look forward to your response.');
    }
}
