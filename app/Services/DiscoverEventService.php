<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Scopes\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DiscoverEventService
{
    /**
     * @return array<string, mixed>
     */
    public function validateFilters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::enum(EventCategory::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'location' => ['nullable', 'string', 'max:255'],
            'pricing' => ['nullable', 'string', Rule::in(['free', 'paid', 'any'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        if (! empty($filters['q'])) {
            $keyword = (string) $filters['q'];
            $query->where(function (Builder $builder) use ($keyword): void {
                $builder->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('starts_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('starts_at', '<=', $filters['to']);
        }

        if (! empty($filters['location'])) {
            $location = (string) $filters['location'];
            $query->where('venue', 'like', "%{$location}%");
        }

        $pricing = $filters['pricing'] ?? 'any';
        if ($pricing === 'free') {
            $query->whereHas('ticketTypes', fn (Builder $ticketQuery) => $ticketQuery
                ->withoutGlobalScope(TenantScope::class)
                ->where('is_active', true)
                ->where('price', 0));
        } elseif ($pricing === 'paid') {
            $query->whereHas('ticketTypes', fn (Builder $ticketQuery) => $ticketQuery
                ->withoutGlobalScope(TenantScope::class)
                ->where('is_active', true)
                ->where('price', '>', 0));
        }

        return $query
            ->with(['organization:id,name,slug'])
            ->withMin(['ticketTypes as min_price' => fn (Builder $q) => $q
                ->withoutGlobalScope(TenantScope::class)
                ->where('is_active', true)], 'price')
            ->withMax(['ticketTypes as max_price' => fn (Builder $q) => $q
                ->withoutGlobalScope(TenantScope::class)
                ->where('is_active', true)], 'price')
            ->orderBy('starts_at')
            ->paginate(perPage: (int) ($filters['per_page'] ?? 15));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Event>
     */
    public function similarEvents(Event $event, int $limit = 4): \Illuminate\Support\Collection
    {
        $query = $this->baseQuery()
            ->where('id', '!=', $event->id)
            ->with(['organization:id,name,slug'])
            ->withMin(['ticketTypes as min_price' => fn (Builder $q) => $q
                ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('is_active', true)], 'price')
            ->withMax(['ticketTypes as max_price' => fn (Builder $q) => $q
                ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('is_active', true)], 'price');

        if ($event->category !== null) {
            $query->where('category', $event->category);
        }

        if ($event->starts_at !== null) {
            $query->whereBetween('starts_at', [
                $event->starts_at->copy()->subDays(30),
                $event->starts_at->copy()->addDays(30),
            ]);
        }

        return $query->get()
            ->sortBy(fn (Event $candidate) => $candidate->starts_at && $event->starts_at
                ? abs($candidate->starts_at->diffInSeconds($event->starts_at))
                : PHP_INT_MAX)
            ->take($limit)
            ->values();
    }

    /**
     * @return list<array{slug: string, updated_at: string|null}>
     */
    public function sitemapEntries(): array
    {
        return $this->baseQuery()
            ->select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (Event $event) => [
                'slug' => $event->slug,
                'updated_at' => $event->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    private function baseQuery(): Builder
    {
        return Event::withoutTenantScope('public discover')
            ->where('status', EventStatus::Published)
            ->where('visibility', EventVisibility::Public);
    }
}
