<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Event;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $exportId,
        public readonly int $eventId,
        public readonly int $userId,
        public readonly string $type,
        public readonly string $format,
    ) {}

    public function handle(ReportExportService $exportService): void
    {
        $event = Event::withoutTenantScope('export job')->find($this->eventId);
        $user = User::query()->find($this->userId);

        if ($event === null || $user === null) {
            return;
        }

        try {
            $path = $exportService->buildStoredExport($event, $this->type, $this->format);
            $exportService->markExportReady($event->organization_id, $this->exportId, $path);
        } catch (\Throwable $exception) {
            Log::error('Report export failed', [
                'export_id' => $this->exportId,
                'event_id' => $this->eventId,
                'type' => $this->type,
                'format' => $this->format,
                'error' => $exception->getMessage(),
            ]);

            $exportService->markExportFailed(
                $event->organization_id,
                $this->exportId,
                'Export generation failed.',
            );
        }
    }
}
