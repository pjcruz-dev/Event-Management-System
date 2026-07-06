<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

final class OrderTicketsNotification extends BaseNotification
{
    /** @param Collection<int, Registration> $registrations */
    public function __construct(
        private readonly Collection $registrations,
        private readonly string $eventName,
        private readonly ?string $customMessage = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $primary = $this->registrations->first();
        $count = $this->registrations->count();

        $bodyLine = $this->customMessage ?? "Your registration is confirmed. You purchased {$count} ticket(s).";

        $message = (new MailMessage)
            ->subject("Your {$count} ticket(s) for {$this->eventName}")
            ->greeting("Hello {$primary?->attendee_first_name},")
            ->line($bodyLine)
            ->line('All ticket PDFs are attached to this email. Each contains a unique QR code for check-in.');

        $disk = Storage::disk(config('filesystems.default'));

        foreach ($this->registrations as $registration) {
            $path = sprintf(
                'tickets/%d/%d/%d.pdf',
                $registration->organization_id,
                $registration->event_id,
                $registration->id,
            );

            if ($disk->exists($path)) {
                $message->attachData(
                    $disk->get($path),
                    "ticket-{$registration->registration_number}.pdf",
                    ['mime' => 'application/pdf'],
                );
            }
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $primary = $this->registrations->first();

        return [
            'event_name' => $this->eventName,
            'ticket_count' => $this->registrations->count(),
            'registration_ids' => $this->registrations->pluck('id')->toArray(),
            'event_id' => $primary?->event_id,
        ];
    }
}
