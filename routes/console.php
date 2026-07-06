<?php

use App\Jobs\IssueEventCertificatesJob;
use App\Jobs\ReleaseExpiredTicketReservationsJob;
use App\Jobs\SendRsvpRemindersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ReleaseExpiredTicketReservationsJob)->everyMinute();
Schedule::job(new IssueEventCertificatesJob)->daily();
Schedule::job(new SendRsvpRemindersJob)->dailyAt('09:00');
