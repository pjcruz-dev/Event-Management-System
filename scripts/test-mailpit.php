<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\GuestInviteStatus;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Notifications\GuestInvitationNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

echo "1. Sending direct SMTP test...\n";

Mail::raw('Event SaaS Mailpit connectivity test — if you see this in Mailpit, SMTP is working.', function ($message): void {
    $message->to('demo@event-saas.test')
        ->subject('Event SaaS — Mailpit test');
});

echo "   Done.\n";

echo "2. Dispatching queued GuestInvitationNotification...\n";

$event = Event::withoutTenantScope('mailpit test')
    ->where('registration_mode', 'rsvp')
    ->first();

if ($event === null) {
    $event = Event::withoutTenantScope('mailpit test')->first();
}

if ($event === null) {
    echo "   Skipped — no events in database. Run: php artisan migrate:fresh --seed\n";
    exit(0);
}

$invite = GuestInvite::withoutTenantScope('mailpit test')->create([
    'organization_id' => $event->organization_id,
    'event_id' => $event->id,
    'first_name' => 'Demo',
    'last_name' => 'Guest',
    'email' => 'guest@example.com',
    'invitation_token' => bin2hex(random_bytes(16)),
    'status' => GuestInviteStatus::Pending,
    'plus_one_limit' => 1,
]);

Notification::route('mail', 'guest@example.com')
    ->notify(new GuestInvitationNotification($invite));

echo "   Queued notification for event: {$event->name}\n";
echo "   Run: php artisan queue:work --once\n";
