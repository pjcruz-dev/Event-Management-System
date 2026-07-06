<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PublicEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::withoutTenantScope('public published events listing')
            ->where('status', EventStatus::Published)
            ->where('visibility', EventVisibility::Public)
            ->orderBy('starts_at');

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->integer('organization_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $events = $query->paginate(
            perPage: min($request->integer('per_page', 15), 50),
        );

        return ApiResponse::paginated($events->through(
            fn (Event $event): EventResource => new EventResource($event),
        ));
    }
}
