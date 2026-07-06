<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Registration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CertificateService
{
    public function issue(Registration $registration, ?array $templateConfig = null): Certificate
    {
        $registration->loadMissing(['event', 'certificate']);

        if ($registration->certificate !== null) {
            return $registration->certificate;
        }

        $path = $this->generatePdf($registration, $templateConfig);

        return Certificate::query()->create([
            'organization_id' => $registration->organization_id,
            'event_id' => $registration->event_id,
            'registration_id' => $registration->id,
            'certificate_number' => 'CERT-'.strtoupper((string) Str::ulid()),
            'issued_at' => now(),
            'template_config' => $templateConfig ?? [],
            'file_path' => $path,
        ]);
    }

    public function issueForEndedEvent(Event $event): int
    {
        if ($event->ends_at === null || $event->ends_at->isFuture()) {
            return 0;
        }

        $count = 0;

        Registration::query()
            ->where('event_id', $event->id)
            ->whereNotNull('checked_in_at')
            ->whereDoesntHave('certificate')
            ->with('order')
            ->chunkById(100, function ($registrations) use (&$count): void {
                foreach ($registrations as $registration) {
                    if ($registration->order?->status !== OrderStatus::Paid) {
                        continue;
                    }

                    $this->issue($registration);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @param  array<string, mixed>|null  $templateConfig
     */
    private function generatePdf(Registration $registration, ?array $templateConfig): string
    {
        $registration->loadMissing('event');

        $html = view('pdf.certificate', [
            'registration' => $registration,
            'event' => $registration->event,
            'headline' => $templateConfig['headline'] ?? 'Certificate of Attendance',
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $path = sprintf(
            'certificates/%d/%d/%d.pdf',
            $registration->organization_id,
            $registration->event_id,
            $registration->id,
        );

        Storage::disk(config('filesystems.default'))->put($path, $dompdf->output());

        return $path;
    }
}
