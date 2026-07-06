<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DiscoverEventResource;
use App\Services\DiscoverEventService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DiscoverController extends Controller
{
    public function index(Request $request, DiscoverEventService $discoverService): JsonResponse
    {
        $filters = $discoverService->validateFilters($request);
        $paginator = $discoverService->paginate($filters);

        return ApiResponse::paginated(
            $paginator->through(fn ($event) => new DiscoverEventResource($event)),
        );
    }

    public function sitemap(DiscoverEventService $discoverService): JsonResponse
    {
        return ApiResponse::success([
            'events' => $discoverService->sitemapEntries(),
        ]);
    }
}
