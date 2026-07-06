<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

final class TicketIssuedNotification extends BaseNotification
{
    public function __construct(
        private readonly Registration $registration,
        private readonly string $pdfPath,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your ticket for '.$this->registration->event->name)
            ->greeting('Hello '.$this->registration->attendee_first_name.',')
            ->line('Your registration is confirmed. Your ticket is attached.')
            ->line('Registration: '.$this->registration->registration_number)
            ->line('Present the QR code on your ticket at check-in.');

        $disk = Storage::disk(config('filesystems.default'));

        if ($disk->exists($this->pdfPath)) {
            $message->attachData(
                $disk->get($this->pdfPath),
                'ticket-'.$this->registration->registration_number.'.pdf',
                ['mime' => 'application/pdf'],
            );
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'registration_id' => $this->registration->id,
            'registration_number' => $this->registration->registration_number,
            'event_id' => $this->registration->event_id,
        ];
    }
}
