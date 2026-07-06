<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\Registration;
use Illuminate\Notifications\Messages\MailMessage;

final class RefundProcessedNotification extends BaseNotification
{
    public function __construct(
        private readonly Order $order,
        private readonly Registration $registration,
        private readonly float $refundAmount,
        private readonly bool $isFullRefund,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('event');
        $eventName = $this->order->event->name ?? 'your event';
        $currency = $this->order->currency ?? 'USD';
        $formatted = $currency.' '.number_format($this->refundAmount, 2);

        $message = (new MailMessage)
            ->subject('Refund processed — '.$eventName)
            ->greeting('Hello '.$this->registration->attendee_first_name.',');

        if ($this->isFullRefund) {
            $message->line('A full refund of **'.$formatted.'** has been processed for your order **'.$this->order->order_number.'**.')
                ->line('Your registration has been cancelled.');
        } else {
            $message->line('A partial refund of **'.$formatted.'** has been processed for your order **'.$this->order->order_number.'**.')
                ->line('Your registration remains active.');
        }

        if ($this->order->refund_reason !== null) {
            $message->line('Reason: '.$this->order->refund_reason);
        }

        return $message->line('The refund should appear in your account within 5–10 business days.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'refund_amount' => $this->refundAmount,
            'is_full_refund' => $this->isFullRefund,
        ];
    }
}
