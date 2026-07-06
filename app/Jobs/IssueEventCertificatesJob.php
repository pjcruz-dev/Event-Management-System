<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Event;
use App\Services\CertificateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class IssueEventCertificatesJob implements ShouldQueue
{
    use Queueable;

    public function handle(CertificateService $certificateService): void
    {
        Event::withoutTenantScope('certificate issuance')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->chunkById(50, function ($events) use ($certificateService): void {
                foreach ($events as $event) {
                    $certificateService->issueForEndedEvent($event);
                }
            });
    }
}
