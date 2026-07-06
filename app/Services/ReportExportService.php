<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Models\Order;
use App\Models\Registration;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportExportService
{
    public function __construct(
        private readonly DashboardMetricsService $metricsService,
        private readonly CacheService $cache,
    ) {}

    /**
     * @return array{export_id: string, status: string, message: string}
     */
    public function queueExport(
        Event $event,
        User $user,
        string $type,
        string $format,
    ): array {
        $exportId = (string) Str::uuid();
        $this->putExportMeta($event->organization_id, $exportId, [
            'status' => 'processing',
            'type' => $type,
            'format' => $format,
            'event_id' => $event->id,
            'user_id' => $user->id,
            'organization_id' => $event->organization_id,
            'created_at' => now()->toIso8601String(),
            'file_path' => null,
            'error' => null,
        ]);

        \App\Jobs\GenerateReportExportJob::dispatch(
            $exportId,
            $event->id,
            $user->id,
            $type,
            $format,
        );

        return [
            'export_id' => $exportId,
            'status' => 'processing',
            'message' => 'Preparing your export. Poll the export status endpoint until ready.',
        ];
    }

    public function shouldQueueExport(Event $event, string $type): bool
    {
        return $this->countRows($event, $type) > (int) config('reports.async_export_row_threshold', 500);
    }

    public function countRows(Event $event, string $type): int
    {
        return match ($type) {
            'registrations' => Registration::query()->where('event_id', $event->id)->count(),
            'orders' => Order::query()->where('event_id', $event->id)->count(),
            'checkins' => Registration::query()
                ->where('event_id', $event->id)
                ->whereNotNull('checked_in_at')
                ->count(),
            'guest-invites' => GuestInvite::query()->where('event_id', $event->id)->count(),
            'seating' => Registration::query()
                ->where('event_id', $event->id)
                ->whereNotNull('table_id')
                ->count()
                + GuestInvite::query()
                    ->where('event_id', $event->id)
                    ->whereNotNull('table_id')
                    ->whereNull('registration_id')
                    ->count(),
            default => 0,
        };
    }

    public function streamExport(Event $event, string $type, string $format): StreamedResponse
    {
        $filename = sprintf('%s-%s-%s.%s', $type, $event->slug, now()->format('Ymd-His'), $format);

        if ($format === 'csv') {
            return $this->streamCsv($event, $type, $filename);
        }

        return $this->streamXlsx($event, $type, $filename);
    }

    public function buildStoredExport(Event $event, string $type, string $format): string
    {
        $disk = Storage::disk(config('filesystems.default'));
        $relativePath = sprintf(
            'exports/%d/%s-%s.%s',
            $event->organization_id,
            $type,
            Str::uuid(),
            $format === 'pdf' ? 'pdf' : $format,
        );

        if ($format === 'pdf') {
            $disk->put($relativePath, $this->buildSummaryPdf($event));

            return $relativePath;
        }

        $absolutePath = $disk->path($relativePath);
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($format === 'csv') {
            $writer = new CsvWriter;
            $writer->openToFile($absolutePath);
            $this->writeExportRows($writer, $event, $type);
            $writer->close();
        } else {
            $writer = new XlsxWriter;
            $writer->openToFile($absolutePath);
            $this->writeExportRows($writer, $event, $type);
            $writer->close();
        }

        return $relativePath;
    }

    public function buildSummaryPdf(Event $event): string
    {
        $metrics = $this->metricsService->forEvent($event);
        $html = view('pdf.dashboard-summary', [
            'event' => $event,
            'metrics' => $metrics,
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExportMeta(int $organizationId, string $exportId): ?array
    {
        /** @var array<string, mixed>|null $meta */
        $meta = $this->cache->get("export:{$exportId}", null, $organizationId);

        return $meta;
    }

    public function markExportReady(int $organizationId, string $exportId, string $filePath): void
    {
        $meta = $this->getExportMeta($organizationId, $exportId);
        if ($meta === null) {
            return;
        }

        $meta['status'] = 'ready';
        $meta['file_path'] = $filePath;
        $meta['completed_at'] = now()->toIso8601String();
        $this->putExportMeta($organizationId, $exportId, $meta);
    }

    public function markExportFailed(int $organizationId, string $exportId, string $error): void
    {
        $meta = $this->getExportMeta($organizationId, $exportId);
        if ($meta === null) {
            return;
        }

        $meta['status'] = 'failed';
        $meta['error'] = $error;
        $this->putExportMeta($organizationId, $exportId, $meta);
    }

    public function downloadResponse(int $organizationId, string $exportId): ?StreamedResponse
    {
        $meta = $this->getExportMeta($organizationId, $exportId);
        if ($meta === null || ($meta['status'] ?? null) !== 'ready' || empty($meta['file_path'])) {
            return null;
        }

        $disk = Storage::disk(config('filesystems.default'));
        $path = (string) $meta['file_path'];

        if (! $disk->exists($path)) {
            return null;
        }

        $filename = basename($path);
        $mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'text/csv',
        };

        return response()->streamDownload(
            static function () use ($disk, $path): void {
                echo $disk->get($path);
            },
            $filename,
            ['Content-Type' => $mime],
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function putExportMeta(int $organizationId, string $exportId, array $meta): void
    {
        $ttl = (int) config('reports.export_retention_hours', 24) * 3600;
        $this->cache->put("export:{$exportId}", $meta, $ttl, $organizationId);
    }

    private function streamCsv(Event $event, string $type, string $filename): StreamedResponse
    {
        return response()->stream(function () use ($event, $type): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $this->headersFor($type));

            foreach ($this->cursorFor($event, $type) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function streamXlsx(Event $event, string $type, string $filename): StreamedResponse
    {
        return response()->stream(function () use ($event, $type): void {
            $writer = new XlsxWriter;
            $writer->openToFile('php://output');
            $this->writeExportRows($writer, $event, $type);
            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

  /**
     * @param  CsvWriter|XlsxWriter  $writer
     */
    private function writeExportRows(object $writer, Event $event, string $type): void
    {
        $writer->addRow(Row::fromValues($this->headersFor($type)));

        foreach ($this->cursorFor($event, $type) as $row) {
            $writer->addRow(Row::fromValues($row));
        }
    }

    /**
     * @return list<string>
     */
    private function headersFor(string $type): array
    {
        return match ($type) {
            'registrations' => [
                'registration_number',
                'first_name',
                'last_name',
                'email',
                'phone',
                'ticket_type',
                'status',
                'country',
                'city',
                'checked_in_at',
                'created_at',
            ],
            'orders' => [
                'order_number',
                'status',
                'subtotal',
                'discount_total',
                'tax_total',
                'total',
                'currency',
                'coupon_code',
                'paid_at',
                'created_at',
            ],
            'checkins' => [
                'registration_number',
                'attendee_name',
                'email',
                'checked_in_at',
                'gate',
                'device_id',
                'checked_in_by',
            ],
            'guest-invites' => [
                'email',
                'first_name',
                'last_name',
                'status',
                'rsvp_response',
                'table',
                'tags',
                'sent_at',
                'responded_at',
                'registration_number',
            ],
            'seating' => [
                'table_name',
                'guest_type',
                'name',
                'email',
                'registration_number',
            ],
            default => throw new \InvalidArgumentException("Unknown export type [{$type}]."),
        };
    }

    /**
     * @return \Generator<int, list<string|int|float|null>>
     */
    private function cursorFor(Event $event, string $type): \Generator
    {
        yield from match ($type) {
            'registrations' => $this->registrationRows($event),
            'orders' => $this->orderRows($event),
            'checkins' => $this->checkInRows($event),
            'guest-invites' => $this->guestInviteRows($event),
            'seating' => $this->seatingRows($event),
            default => throw new \InvalidArgumentException("Unknown export type [{$type}]."),
        };
    }

    /**
     * @return \Generator<int, list<string|int|float|null>>
     */
    private function registrationRows(Event $event): \Generator
    {
        $query = Registration::query()
            ->where('event_id', $event->id)
            ->with('ticketType')
            ->orderBy('id');

        foreach ($query->cursor() as $registration) {
            $custom = $registration->custom_fields ?? [];

            yield [
                $registration->registration_number,
                $registration->attendee_first_name,
                $registration->attendee_last_name,
                $registration->attendee_email,
                $registration->attendee_phone,
                $registration->ticketType?->name,
                $registration->status->value,
                $custom['country'] ?? null,
                $custom['city'] ?? null,
                $registration->checked_in_at?->toIso8601String(),
                $registration->created_at?->toIso8601String(),
            ];
        }
    }

    /**
     * @return \Generator<int, list<string|int|float|null>>
     */
    private function orderRows(Event $event): \Generator
    {
        $query = Order::query()
            ->where('event_id', $event->id)
            ->with('coupon')
            ->orderBy('id');

        foreach ($query->cursor() as $order) {
            yield [
                $order->order_number,
                $order->status->value,
                (float) $order->subtotal,
                (float) $order->discount_total,
                (float) $order->tax_total,
                (float) $order->total,
                $order->currency,
                $order->coupon?->code,
                $order->paid_at?->toIso8601String(),
                $order->created_at?->toIso8601String(),
            ];
        }
    }

    /**
     * @return \Generator<int, list<string|int|float|null>>
     */
    private function checkInRows(Event $event): \Generator
    {
        $query = Registration::query()
            ->where('event_id', $event->id)
            ->whereNotNull('checked_in_at')
            ->with('checkedInBy')
            ->orderBy('checked_in_at');

        foreach ($query->cursor() as $registration) {
            yield [
                $registration->registration_number,
                trim($registration->attendee_first_name.' '.$registration->attendee_last_name),
                $registration->attendee_email,
                $registration->checked_in_at?->toIso8601String(),
                $registration->check_in_gate,
                $registration->check_in_device_id,
                $registration->checkedInBy?->name,
            ];
        }
    }

    /**
     * @return \Generator<int, list<string|int|float|null>>
     */
    private function guestInviteRows(Event $event): \Generator
    {
        $query = GuestInvite::query()
            ->where('event_id', $event->id)
            ->with(['table', 'registration'])
            ->orderBy('id');

        foreach ($query->cursor() as $invite) {
            yield [
                $invite->email,
                $invite->first_name,
                $invite->last_name,
                $invite->status->value,
                $invite->rsvp_response?->value,
                $invite->table?->name,
                implode(', ', $invite->tags ?? []),
                $invite->sent_at?->toIso8601String(),
                $invite->responded_at?->toIso8601String(),
                $invite->registration?->registration_number,
            ];
        }
    }

    /**
     * @return \Generator<int, list<string|int|float|null>>
     */
    private function seatingRows(Event $event): \Generator
    {
        $registrations = Registration::query()
            ->where('event_id', $event->id)
            ->whereNotNull('table_id')
            ->with('table')
            ->orderBy('table_id')
            ->cursor();

        foreach ($registrations as $registration) {
            yield [
                $registration->table?->name,
                $registration->is_plus_one ? 'plus_one' : 'registration',
                trim($registration->attendee_first_name.' '.$registration->attendee_last_name),
                $registration->attendee_email,
                $registration->registration_number,
            ];
        }

        $invites = GuestInvite::query()
            ->where('event_id', $event->id)
            ->whereNotNull('table_id')
            ->whereNull('registration_id')
            ->with('table')
            ->orderBy('table_id')
            ->cursor();

        foreach ($invites as $invite) {
            yield [
                $invite->table?->name,
                'invite',
                $invite->displayName(),
                $invite->email,
                null,
            ];
        }
    }

    public function validateExportRequest(Request $request): array
    {
        return $request->validate([
            'format' => ['required', 'string', 'in:csv,xlsx,pdf'],
            'async' => ['sometimes', 'boolean'],
        ]);
    }

    public function validateExportType(string $type): string
    {
        if (! in_array($type, ['registrations', 'orders', 'checkins', 'guest-invites', 'seating', 'summary'], true)) {
            throw new \InvalidArgumentException("Unknown export type [{$type}].");
        }

        return $type;
    }
}
