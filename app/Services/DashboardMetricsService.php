<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GuestInviteStatus;
use App\Enums\OrderStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RsvpResponse;
use App\Models\Event;
use App\Models\GuestInvite;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;

final class DashboardMetricsService
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forEvent(Event $event): array
    {
        $organizationId = $event->organization_id;
        $cacheKey = "dashboard:event:{$event->id}:metrics";

        return $this->cache->remember(
            $cacheKey,
            $this->cacheTtl(),
            function () use ($event, $organizationId): array {
                $org = Organization::query()->find($organizationId);
                $fallbackCurrency = $org?->defaultCurrency() ?? 'USD';

                return $this->computeEventMetrics($event, $fallbackCurrency);
            },
            $organizationId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forOrganization(Organization $organization): array
    {
        $cacheKey = 'dashboard:organization:metrics';

        return $this->cache->remember(
            $cacheKey,
            $this->cacheTtl(),
            fn (): array => $this->computeOrganizationMetrics($organization),
            $organization->id,
        );
    }

    public function invalidateForEvent(Event $event): void
    {
        $this->cache->forget("dashboard:event:{$event->id}:metrics", $event->organization_id);
        $this->cache->forget('dashboard:organization:metrics', $event->organization_id);
    }

  private function cacheTtl(): int
    {
        return (int) config('reports.metrics_cache_ttl', 60);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeEventMetrics(Event $event, string $fallbackCurrency = 'USD'): array
    {
        $eventId = $event->id;

        $paidOrders = Order::query()
            ->where('event_id', $eventId)
            ->where('status', OrderStatus::Paid);

        $totalRevenue = (float) (clone $paidOrders)->sum('total');
        $currency = (clone $paidOrders)->value('currency')
            ?? $event->ticketTypes()->value('currency')
            ?? $fallbackCurrency;

        $confirmedRegistrations = Registration::query()
            ->where('event_id', $eventId)
            ->where('status', RegistrationStatus::Confirmed)
            ->count();

        $checkedInCount = Registration::query()
            ->where('event_id', $eventId)
            ->whereNotNull('checked_in_at')
            ->count();

        $eligibleForCheckIn = Registration::query()
            ->where('event_id', $eventId)
            ->where('status', RegistrationStatus::Confirmed)
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Paid))
            ->count();

        $revenueOverTime = Order::query()
            ->where('event_id', $eventId)
            ->where('status', OrderStatus::Paid)
            ->whereNotNull('paid_at')
            ->selectRaw('DATE(paid_at) as date, SUM(total) as total')
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->all();

        $registrationsOverTime = Registration::query()
            ->where('event_id', $eventId)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $topTicketTypes = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_types', 'ticket_types.id', '=', 'order_items.ticket_type_id')
            ->where('orders.event_id', $eventId)
            ->where('orders.status', OrderStatus::Paid->value)
            ->selectRaw('ticket_types.id as ticket_type_id, ticket_types.name as name, SUM(order_items.quantity) as volume, SUM(order_items.total_price) as revenue')
            ->groupBy('ticket_types.id', 'ticket_types.name')
            ->orderByDesc('volume')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'ticket_type_id' => (int) $row->ticket_type_id,
                'name' => (string) $row->name,
                'volume' => (int) $row->volume,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();

        $countryExpression = $this->jsonFieldExpression('custom_fields', 'country');
        $cityExpression = $this->jsonFieldExpression('custom_fields', 'city');

        $geographicBreakdown = Registration::query()
            ->where('event_id', $eventId)
            ->selectRaw("
                {$countryExpression} as country,
                {$cityExpression} as city,
                COUNT(*) as count
            ")
            ->groupBy('country', 'city')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'country' => (string) $row->country,
                'city' => (string) $row->city,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $couponUsage = Order::query()
            ->from('orders')
            ->where('orders.event_id', $eventId)
            ->where('orders.status', OrderStatus::Paid)
            ->whereNotNull('orders.coupon_id')
            ->join('coupons', 'coupons.id', '=', 'orders.coupon_id')
            ->selectRaw('coupons.id as coupon_id, coupons.code as code, COUNT(*) as uses, SUM(orders.discount_total) as discount_total')
            ->groupBy('coupons.id', 'coupons.code')
            ->orderByDesc('uses')
            ->get()
            ->map(fn ($row) => [
                'coupon_id' => (int) $row->coupon_id,
                'code' => (string) $row->code,
                'uses' => (int) $row->uses,
                'discount_total' => round((float) $row->discount_total, 2),
            ])
            ->values()
            ->all();

        $invitedCount = GuestInvite::query()->where('event_id', $eventId)->count();
        $sentCount = GuestInvite::query()
            ->where('event_id', $eventId)
            ->whereNotNull('sent_at')
            ->count();
        $acceptedCount = GuestInvite::query()
            ->where('event_id', $eventId)
            ->where('rsvp_response', RsvpResponse::Accepted)
            ->count();
        $declinedCount = GuestInvite::query()
            ->where('event_id', $eventId)
            ->where(function ($query): void {
                $query->where('rsvp_response', RsvpResponse::Declined)
                    ->orWhere('status', GuestInviteStatus::Declined);
            })
            ->count();
        $maybeCount = GuestInvite::query()
            ->where('event_id', $eventId)
            ->where('rsvp_response', RsvpResponse::Maybe)
            ->count();
        $respondedCount = GuestInvite::query()
            ->where('event_id', $eventId)
            ->whereNotNull('responded_at')
            ->count();
        $plusOneCount = Registration::query()
            ->where('event_id', $eventId)
            ->where('is_plus_one', true)
            ->count();

        return [
            'event_id' => $eventId,
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'currency' => $currency,
                'total_registrations' => Registration::query()->where('event_id', $eventId)->count(),
                'confirmed_registrations' => $confirmedRegistrations,
                'checked_in_count' => $checkedInCount,
                'check_in_rate' => $eligibleForCheckIn > 0
                    ? round($checkedInCount / $eligibleForCheckIn, 4)
                    : 0.0,
                'orders_with_coupon' => (clone $paidOrders)->whereNotNull('coupon_id')->count(),
                'rsvp_invited' => $invitedCount,
                'rsvp_sent' => $sentCount,
                'rsvp_accepted' => $acceptedCount,
                'rsvp_declined' => $declinedCount,
                'rsvp_maybe' => $maybeCount,
                'rsvp_response_rate' => $invitedCount > 0
                    ? round($respondedCount / $invitedCount, 4)
                    : 0.0,
                'rsvp_plus_one_count' => $plusOneCount,
            ],
            'revenue_over_time' => $revenueOverTime,
            'registrations_over_time' => $registrationsOverTime,
            'top_ticket_types' => $topTicketTypes,
            'geographic_breakdown' => $geographicBreakdown,
            'coupon_usage' => $couponUsage,
            'cached_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function computeOrganizationMetrics(Organization $organization): array
    {
        $events = Event::query()
            ->where('organization_id', $organization->id)
            ->orderBy('starts_at')
            ->get();

        $orgCurrency = $organization->defaultCurrency();

        $eventMetrics = $events->map(function (Event $event) use ($orgCurrency): array {
            $metrics = $this->computeEventMetrics($event, $orgCurrency);

            return [
                'event_id' => $event->id,
                'name' => $event->name,
                'status' => $event->status->value,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'total_revenue' => $metrics['summary']['total_revenue'],
                'currency' => $metrics['summary']['currency'],
                'confirmed_registrations' => $metrics['summary']['confirmed_registrations'],
                'checked_in_count' => $metrics['summary']['checked_in_count'],
                'check_in_rate' => $metrics['summary']['check_in_rate'],
            ];
        })->values()->all();

        $totalRevenue = array_sum(array_column($eventMetrics, 'total_revenue'));
        $totalRegistrations = array_sum(array_column($eventMetrics, 'confirmed_registrations'));
        $totalCheckedIn = array_sum(array_column($eventMetrics, 'checked_in_count'));

        return [
            'organization_id' => $organization->id,
            'events' => $eventMetrics,
            'totals' => [
                'total_revenue' => round($totalRevenue, 2),
                'currency' => $organization->defaultCurrency(),
                'confirmed_registrations' => $totalRegistrations,
                'checked_in_count' => $totalCheckedIn,
                'event_count' => count($eventMetrics),
            ],
            'cached_at' => now()->toIso8601String(),
        ];
    }

    private function jsonFieldExpression(string $column, string $key): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "COALESCE(NULLIF(json_extract({$column}, '$.{$key}'), ''), 'Unknown')";
        }

        return "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.{$key}')), ''), 'Unknown')";
    }
}
