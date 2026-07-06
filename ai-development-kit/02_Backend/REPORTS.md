# REPORTS.md

## Available Metrics (`DashboardMetricsService`)

Per event:

- **Summary** — total revenue (paid orders), confirmed registrations, check-in rate,
  checked-in count, coupon order count
- **Revenue over time** — daily `SUM(total)` from paid orders (`paid_at`)
- **Registrations over time** — daily registration count (`created_at`)
- **Top ticket types** — volume and revenue from paid `order_items`
- **Geographic breakdown** — `country` / `city` keys from registration
  `custom_fields` JSON (falls back to `Unknown` when not collected)
- **Coupon usage** — uses and discount totals per coupon code

Per organization:

- Rollup of all events with per-event revenue, registrations, check-in rate
- Organization totals across events

All metrics use SQL-level aggregation (`GROUP BY`, `SUM`, `COUNT`) — rows are
never loaded in bulk into PHP for summing.

## Caching

**Approach:** short TTL expiry **plus** targeted invalidation on high-signal writes.

| Setting | Default | Env override |
|---|---|---|
| Metrics TTL | 60 seconds | `REPORTS_METRICS_CACHE_TTL` |
| Async export threshold | 500 rows | `REPORTS_ASYNC_EXPORT_THRESHOLD` |
| Export file retention | 24 hours | `REPORTS_EXPORT_RETENTION_HOURS` |

**Cache keys** (via tenant-scoped `CacheService`):

- `org:{organization_id}:dashboard:event:{event_id}:metrics`
- `org:{organization_id}:dashboard:organization:metrics`
- `org:{organization_id}:export:{export_id}` (async export status)

**Invalidation:** `InvalidateDashboardMetricsCache` listener runs on
`OrderPaid` and `RegistrationCheckedIn`, forgetting the affected event key
and the organization rollup key. Other writes (e.g. manual registration edits)
rely on TTL expiry rather than exhaustive invalidation hooks.

## Exports

| Export | Format | Delivery |
|---|---|---|
| Registrations list | CSV, XLSX | Streamed inline under row threshold; queued job above threshold |
| Orders / revenue | CSV, XLSX | Streamed / queued (same rules) |
| Check-in log | CSV, XLSX | Streamed / queued |
| Summary report | PDF | Inline download or queued job |

**Streaming:** exports iterate with Eloquent `cursor()` — never `Model::all()`.

**Large exports:** `POST /events/{event}/exports/{type}` with `{ "format": "csv|xlsx|pdf", "async": true }`
returns `{ export_id, status: "processing" }`. Poll `GET /exports/{exportId}` until
`status` is `ready`, then download via `GET /exports/{exportId}/download`.

Files are stored at `exports/{organization_id}/...` on the default disk.

**Authorization:** `reports.export` permission required for export endpoints.
Event/organization scoping is enforced by tenant middleware and policies — Org A
cannot export Org B data by substituting an event id.

## Activity Feed

`ActivityLog` entries surfaced via:

- `GET /organization/activity` — org-wide, filterable
- `GET /events/{event}/activity` — scoped to `metadata.event_id`

Filters: `actor_id`, `action`, `from`, `to`, `per_page` (max 100).

## Frontend

- Event analytics: `/events/[id]/analytics` (Recharts)
- Organization rollup: `/organization/analytics`
- Activity viewer: `/organization/activity`
- Export UI shows “Preparing your export…” while polling async jobs

## Tests

`tests/Feature/Dashboard/` covers:

- Revenue totals match paid order sums
- Cache invalidation after check-in
- Export tenant isolation
- Export implementation uses `cursor()` (not `all()`)
