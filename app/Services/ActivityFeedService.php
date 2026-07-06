<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ActivityFeedService
{
    /**
     * @param  array{event_id?: int|null, actor_id?: int|null, action?: string|null, from?: string|null, to?: string|null}  $filters
     */
    public function paginate(
        Organization $organization,
        array $filters,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $query = ActivityLog::query()
            ->where('organization_id', $organization->id)
            ->with('actor');

        if (! empty($filters['event_id'])) {
            $query->where('metadata->event_id', (int) $filters['event_id']);
        }

        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', (int) $filters['actor_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->latest()->paginate(min($perPage, 100));
    }
}
