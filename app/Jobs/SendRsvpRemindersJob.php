<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\GuestInviteStatus;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Notifications\RsvpReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendRsvpRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $events = Event::withoutTenantScope('rsvp reminders scheduler')
            ->whereIn('registration_mode', ['rsvp', 'invite_only'])
            ->whereNotNull('rsvp_settings')
            ->where('status', 'published')
            ->get();

        foreach ($events as $event) {
            $settings = $event->resolvedRsvpSettings();

            if (! ($settings['auto_send_reminders'] ?? false)) {
                continue;
            }

            $deadline = $settings['response_deadline'] ?? null;
            if ($deadline === null) {
                continue;
            }

            $deadlineDate = \Carbon\Carbon::parse($deadline);
            $daysBefore = (int) ($settings['reminder_days_before_deadline'] ?? 3);
            $reminderDate = $deadlineDate->copy()->subDays($daysBefore);
            $now = now();

            if ($now->lt($reminderDate) || $now->gt($deadlineDate)) {
                continue;
            }

            $this->sendRemindersForEvent($event);
        }
    }

    private function sendRemindersForEvent(Event $event): void
    {
        $invites = GuestInvite::withoutTenantScope('rsvp reminders send')
            ->where('event_id', $event->id)
            ->whereIn('status', [
                GuestInviteStatus::Sent->value,
                GuestInviteStatus::Opened->value,
            ])
            ->whereNull('rsvp_response')
            ->where(function ($query): void {
                $query->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<', now()->subDay());
            })
            ->get();

        foreach ($invites as $invite) {
            $notifiable = new AnonymousNotifiable;
            $notifiable->route('mail', $invite->email);
            $notifiable->notify(new RsvpReminderNotification($invite));

            $invite->update([
                'last_reminder_sent_at' => now(),
                'reminder_count' => $invite->reminder_count + 1,
            ]);
        }
    }
}
