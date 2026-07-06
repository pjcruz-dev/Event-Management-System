<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

final class BadgeService
{
    public function __construct(
        private readonly QrTokenService $qrTokenService,
    ) {}

    public function generate(Registration $registration): string
    {
        $registration->loadMissing(['event', 'ticketType']);

        $token = $this->qrTokenService->resolveToken($registration);

        if ($token === null) {
            throw new \RuntimeException('Badge generation requires a paid registration with an issued QR token.');
        }

        $qrDataUri = $this->qrDataUri($token);
        $theme = $registration->event->theme_config ?? [];
        $primaryColor = is_array($theme) ? ($theme['primary_color'] ?? '#1E40AF') : '#1E40AF';

        $html = view('pdf.badge', [
            'registration' => $registration,
            'event' => $registration->event,
            'qrDataUri' => $qrDataUri,
            'primaryColor' => $primaryColor,
            'jobTitle' => $registration->custom_fields['job_title'] ?? null,
            'company' => $registration->custom_fields['company'] ?? null,
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 288, 432], 'portrait');
        $dompdf->render();

        $path = sprintf(
            'badges/%d/%d/%d.pdf',
            $registration->organization_id,
            $registration->event_id,
            $registration->id,
        );

        Storage::disk(config('filesystems.default'))->put($path, $dompdf->output());

        return $path;
    }

    public function contents(string $path): ?string
    {
        $disk = Storage::disk(config('filesystems.default'));

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    private function qrDataUri(string $token): string
    {
        $result = (new Builder())->build(
            data: $token,
            size: 180,
            margin: 8,
        );

        return 'data:image/png;base64,'.base64_encode($result->getString());
    }
}
