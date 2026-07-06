<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Organization;
use App\Models\Registration;
use App\Notifications\OrderTicketsNotification;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class SendOrderTicketsNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Delay first attempt so QR + PDF generation jobs finish first.
     */
    public int $delay = 30;

    public int $tries = 5;

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(OrderPaid $event): void
    {
        $this->ensureTenantContext($event->organizationId);

        $order = $event->order->fresh(['registrations', 'event']);

        if ($order === null) {
            return;
        }

        // Prevent duplicate sends regardless of how many times this job runs.
        if (! Cache::add('order-tickets-sent:'.$order->id, true, now()->addHour())) {
            return;
        }

        $registrations = $order->registrations;

        if ($registrations->isEmpty()) {
            $legacy = $order->registration;
            if ($legacy !== null) {
                $registrations = Registration::newCollection([$legacy]);
            }
        }

        if ($registrations->isEmpty()) {
            return;
        }

        $disk = Storage::disk(config('filesystems.default'));
        $allPdfsReady = $registrations->every(function (Registration $reg) use ($disk): bool {
            $path = sprintf('tickets/%d/%d/%d.pdf', $reg->organization_id, $reg->event_id, $reg->id);

            return $disk->exists($path);
        });

        if (! $allPdfsReady) {
            Cache::forget('order-tickets-sent:'.$order->id);
            $this->release(15);

            return;
        }

        $primaryEmail = $registrations->first()->attendee_email;

        $confirmationSettings = $order->event->resolvedConfirmationSettings();
        $customMessage = $confirmationSettings['registration_confirmed_message'] ?? null;

        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $primaryEmail);
        $notifiable->notify(new OrderTicketsNotification($registrations, $order->event->name, $customMessage));
    }

    private function ensureTenantContext(int $organizationId): void
    {
        if ($this->tenantContext->isResolved()) {
            return;
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization !== null) {
            $this->tenantContext->set($organization);
        }
    }
}
