<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard metrics cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Metrics are cached per-event and per-organization with this TTL.
    | Cache is explicitly invalidated on OrderPaid and RegistrationCheckedIn;
    | other writes rely on TTL expiry.
    |
    */
    'metrics_cache_ttl' => (int) env('REPORTS_METRICS_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Async export threshold
    |--------------------------------------------------------------------------
    |
    | Exports exceeding this row count are queued instead of streamed inline.
    |
    */
    'async_export_row_threshold' => (int) env('REPORTS_ASYNC_EXPORT_THRESHOLD', 500),

    /*
    |--------------------------------------------------------------------------
    | Export file retention (hours)
    |--------------------------------------------------------------------------
    */
    'export_retention_hours' => (int) env('REPORTS_EXPORT_RETENTION_HOURS', 24),
];
