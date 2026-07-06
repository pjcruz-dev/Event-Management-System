<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\Storage;

final class TicketPdfService
{
    public function generate(Registration $registration, string $qrToken): string
    {
        $registration->loadMissing(['event', 'ticketType', 'table']);

        $qrDataUri = $this->qrDataUri($qrToken);
        $html = view('pdf.ticket', [
            'registration' => $registration,
            'event' => $registration->event,
            'ticketType' => $registration->ticketType,
            'tableName' => $registration->table?->name,
            'qrDataUri' => $qrDataUri,
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $path = sprintf(
            'tickets/%d/%d/%d.pdf',
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
            size: 240,
            margin: 10,
        );

        return 'data:image/png;base64,'.base64_encode($result->getString());
    }
}
