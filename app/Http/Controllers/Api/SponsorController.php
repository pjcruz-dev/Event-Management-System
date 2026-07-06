<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SponsorTier;
use App\Http\Controllers\Controller;
use App\Http\Resources\SponsorResource;
use App\Models\Event;
use App\Models\Sponsor;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class SponsorController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return ApiResponse::success(
            SponsorResource::collection($event->sponsors()->orderBy('sort_order')->get()),
        );
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('create', Sponsor::class);
        $this->authorize('update', $event);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'tier' => ['required', 'string', Rule::enum(SponsorTier::class)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $sponsor = $event->sponsors()->create([
            ...$data,
            'organization_id' => $event->organization_id,
        ]);

        return ApiResponse::created(new SponsorResource($sponsor));
    }

    public function update(Request $request, Event $event, Sponsor $sponsor): JsonResponse
    {
        $this->authorize('update', $sponsor);
        abort_unless($sponsor->event_id === $event->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'tier' => ['sometimes', 'string', Rule::enum(SponsorTier::class)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $sponsor->update($data);

        return ApiResponse::success(new SponsorResource($sponsor->fresh()));
    }

    public function destroy(Event $event, Sponsor $sponsor): JsonResponse
    {
        $this->authorize('delete', $sponsor);
        abort_unless($sponsor->event_id === $event->id, 404);
        $sponsor->delete();

        return ApiResponse::success(message: 'Sponsor deleted.');
    }
}
