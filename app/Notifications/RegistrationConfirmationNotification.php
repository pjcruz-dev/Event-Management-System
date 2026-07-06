<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\Registration;
use Illuminate\Notifications\Messages\MailMessage;

final class RegistrationConfirmationNotification extends BaseNotification
{
    public function __construct(
        private readonly Registration $registration,
        private readonly Order $order,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('event');

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $slug = $this->order->event->slug;
        $payUrl = $frontendUrl.'/e/'.$slug.'/register/checkout?order='.$this->order->order_number;

        $confirmationSettings = $this->order->event->resolvedConfirmationSettings();
        $customLine = $confirmationSettings['registration_pending_message'] ?? null;

        $message = (new MailMessage)
            ->subject('Registration received — payment pending')
            ->greeting('Hello '.$this->registration->attendee_first_name.',');

        if (! empty($customLine)) {
            $message->line($customLine);
        } else {
            $message->line('We have received your registration.');
        }

        return $message
            ->line('Order: '.$this->order->order_number)
            ->line('Total due: '.$this->order->currency.' '.number_format((float) $this->order->total, 2))
            ->line('Payment is pending — click the button below to complete your purchase.')
            ->action('Pay now', $payUrl)
            ->line('If the button above does not work, copy and paste this link into your browser:')
            ->line($payUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'registration_id' => $this->registration->id,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status->value,
        ];
    }
}
