<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSession;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ConferenceAnalyticsController extends Controller
{
    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $sessionStats = EventSession::query()
            ->where('event_id', $event->id)
            ->withCount('sessionRegistrations')
            ->get()
            ->map(fn (EventSession $session) => [
                'session_id' => $session->id,
                'title' => $session->title,
                'registered_count' => $session->session_registrations_count,
                'capacity' => $session->capacity,
            ]);

        $exhibitorStats = $event->exhibitors()
            ->withCount('leads')
            ->get()
            ->map(fn ($exhibitor) => [
                'exhibitor_id' => $exhibitor->id,
                'name' => $exhibitor->name,
                'leads_count' => $exhibitor->leads_count,
            ]);

        $sponsorStats = $event->sponsors()
            ->select('id', 'name', 'tier')
            ->get()
            ->map(fn ($sponsor) => [
                'sponsor_id' => $sponsor->id,
                'name' => $sponsor->name,
                'tier' => $sponsor->tier->value,
            ]);

        return ApiResponse::success([
            'sessions' => $sessionStats,
            'exhibitors' => $exhibitorStats,
            'sponsors' => $sponsorStats,
        ]);
    }
}
