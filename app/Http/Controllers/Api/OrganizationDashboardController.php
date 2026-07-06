<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\Event;
use App\Services\ActivityFeedService;
use App\Services\DashboardMetricsService;
use App\Services\TenantContext;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrganizationDashboardController extends Controller
{
    public function metrics(
        TenantContext $tenantContext,
        DashboardMetricsService $metricsService,
    ): JsonResponse {
        $organization = $tenantContext->get();
        abort_if($organization === null, 403);

        return ApiResponse::success($metricsService->forOrganization($organization));
    }

    public function activity(
        Request $request,
        TenantContext $tenantContext,
        ActivityFeedService $activityFeedService,
    ): JsonResponse {
        $organization = $tenantContext->get();
        abort_if($organization === null, 403);

        $filters = $request->validate([
            'event_id' => ['nullable', 'integer'],
            'actor_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 25);

        $paginator = $activityFeedService->paginate($organization, $filters, $perPage);

        return ApiResponse::paginated(
            $paginator->through(fn ($log) => new ActivityLogResource($log)),
        );
    }

    public function eventActivity(
        Request $request,
        Event $event,
        ActivityFeedService $activityFeedService,
    ): JsonResponse {
        $this->authorize('view', $event);

        $filters = $request->validate([
            'actor_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filters['event_id'] = $event->id;
        $perPage = (int) ($filters['per_page'] ?? 25);

        $paginator = $activityFeedService->paginate(
            $event->organization,
            $filters,
            $perPage,
        );

        return ApiResponse::paginated(
            $paginator->through(fn ($log) => new ActivityLogResource($log)),
        );
    }
}
