<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\Event;
use App\Models\Registration;
use App\Services\ActivityFeedService;
use App\Services\DashboardMetricsService;
use App\Services\ReportExportService;
use App\Services\TenantContext;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class EventDashboardController extends Controller
{
    public function metrics(Event $event, DashboardMetricsService $metricsService): JsonResponse
    {
        $this->authorize('view', $event);

        return ApiResponse::success($metricsService->forEvent($event));
    }

    public function dietarySummary(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $registrations = Registration::query()
            ->where('event_id', $event->id)
            ->whereNotNull('custom_fields')
            ->get(['custom_fields']);

        $counts = [];
        $total = 0;

        foreach ($registrations as $reg) {
            $meal = $reg->custom_fields['meal_preference'] ?? null;
            if ($meal !== null && $meal !== '') {
                $counts[$meal] = ($counts[$meal] ?? 0) + 1;
                $total++;
            }
        }

        arsort($counts);

        $summary = [];
        foreach ($counts as $option => $count) {
            $summary[] = [
                'option' => $option,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return ApiResponse::success([
            'total_responses' => $total,
            'total_registrations' => Registration::query()->where('event_id', $event->id)->count(),
            'options' => $summary,
        ]);
    }

    public function export(
        Request $request,
        Event $event,
        string $type,
        ReportExportService $exportService,
    ): JsonResponse|StreamedResponse {
        $this->authorize('view', $event);

        $validated = $exportService->validateExportRequest($request);

        try {
            $exportService->validateExportType($type);
        } catch (\InvalidArgumentException) {
            return ApiResponse::error('Unknown export type.', status: 404);
        }

        $format = $validated['format'];
        $async = (bool) ($validated['async'] ?? false);

        if ($type === 'summary') {
            if ($format !== 'pdf') {
                return ApiResponse::error('Summary exports are only available as PDF.', status: 422);
            }

            if ($async || $exportService->shouldQueueExport($event, $type)) {
                return ApiResponse::success(
                    $exportService->queueExport($event, $request->user(), $type, $format),
                );
            }

            return response()->streamDownload(
                static fn () => print($exportService->buildSummaryPdf($event)),
                sprintf('summary-%s-%s.pdf', $event->slug, now()->format('Ymd-His')),
                ['Content-Type' => 'application/pdf'],
            );
        }

        if ($format === 'pdf') {
            return ApiResponse::error('PDF is only supported for summary exports.', status: 422);
        }

        if ($async || $exportService->shouldQueueExport($event, $type)) {
            return ApiResponse::success(
                $exportService->queueExport($event, $request->user(), $type, $format),
            );
        }

        return $exportService->streamExport($event, $type, $format);
    }

    public function exportStatus(
        Request $request,
        string $exportId,
        ReportExportService $exportService,
        TenantContext $tenantContext,
    ): JsonResponse {
        $organization = $tenantContext->get();
        abort_if($organization === null, 403);

        $meta = $exportService->getExportMeta($organization->id, $exportId);

        if ($meta === null) {
            return ApiResponse::error('Export not found.', status: 404);
        }

        return ApiResponse::success([
            'export_id' => $exportId,
            'status' => $meta['status'],
            'type' => $meta['type'],
            'format' => $meta['format'],
            'error' => $meta['error'] ?? null,
            'download_url' => ($meta['status'] ?? null) === 'ready'
                ? url("/api/v1/exports/{$exportId}/download")
                : null,
        ]);
    }

    public function downloadExport(
        Request $request,
        string $exportId,
        ReportExportService $exportService,
        TenantContext $tenantContext,
    ): StreamedResponse|JsonResponse {
        $organization = $tenantContext->get();
        abort_if($organization === null, 403);

        $response = $exportService->downloadResponse($organization->id, $exportId);

        if ($response === null) {
            return ApiResponse::error('Export is not ready or has expired.', status: 404);
        }

        return $response;
    }
}
