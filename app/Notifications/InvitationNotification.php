<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Invitation $invitation,
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
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $acceptUrl = $frontendUrl.'/register?invitation_token='.$this->invitation->token;

        return (new MailMessage)
            ->subject('You have been invited to '.$this->invitation->organization->name)
            ->greeting('Hello!')
            ->line('You have been invited to join **'.$this->invitation->organization->name.'** as **'.$this->invitation->role.'**.')
            ->action('Accept invitation', $acceptUrl)
            ->line('This invitation expires on '.$this->invitation->expires_at->toDayDateTimeString().'.');
    }
}
