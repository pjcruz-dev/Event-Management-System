# PHASE 9 — Organizer Dashboard & Reporting

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 3–8 (this phase mostly aggregates existing data —
> few if any new domain models, mostly read-side services and UI).

## Objective

Give organizers a real-time operational view across all their events:
revenue, attendance, check-ins, and exportable reports — plus the
activity/audit trail accumulated by earlier phases.

## Backend Tasks

### Dashboard Aggregation Services
- `DashboardMetricsService` — revenue over time, registrations over
  time, check-in rate, top ticket types by volume, geographic breakdown
  (country/city from registration address if collected), coupon usage.
- Design for performance: these are read-heavy aggregate queries.
  Use query-level aggregation (not loading all rows into PHP), and cache
  results per-event with a short TTL (Phase 0's `CacheService`,
  tenant-scoped key), invalidated on relevant writes (new order,
  check-in) or simply left to expire — state which approach you chose.

### Reports & Exports
- CSV and Excel export endpoints for: registrations list, orders/
  revenue, check-in log — streamed, not loaded fully into memory, so
  large events don't time out or exhaust memory.
- PDF export for a summary report (the dashboard's key metrics as a
  shareable one-pager).

### Activity Feed
- Surface `ActivityLog` entries (accumulated since Phase 1) in a
  paginated, filterable (by actor, action type, date range) feed per
  organization and per event.

## Frontend Tasks

- `(dashboard)/events/[id]/analytics` — chart-based dashboard: revenue
  trend, registrations trend, check-in rate gauge, top ticket types,
  geographic breakdown map/table. Use a charting library consistent with
  the stack (Recharts pairs well with shadcn/ui/Tailwind).
- `(dashboard)/organization/analytics` — cross-event rollup for the
  whole organization (all events' revenue/attendance side by side).
- Export buttons (CSV/Excel/PDF) with a background-job pattern for large
  exports (show "preparing your export, we'll notify you" rather than
  blocking the UI thread on a huge synchronous download).
- `(dashboard)/organization/activity` — activity/audit log viewer with
  filters.
- Dashboard widgets should respect the dark mode and responsive
  requirements already established — this is the most visually dense
  screen in the product, so pay particular attention to information
  density on mobile (collapse to key metrics + a "view details" link
  rather than cramming every chart).

## Tests

- Metrics service returns correct aggregates against known seeded data
  (use Phase 3's seeder or a dedicated fixture) — e.g., assert revenue
  total matches the sum of paid orders exactly.
- Export endpoints respect tenant scoping (Org A cannot export Org B's
  data by manipulating the event id in the request).
- Large-dataset export test confirms streaming (memory usage doesn't
  scale linearly with row count in an obviously naive way) — at minimum,
  confirm the implementation uses chunked/streamed queries, not
  `Model::all()`.

## Definition of Done

- An organizer can open an event's analytics page and see accurate,
  real-time-ish metrics reflecting seeded/test data, then export a CSV
  and receive correct data.
- `REPORTS.md` documents available metrics, export formats, and the
  caching/invalidation approach chosen.
- List every file created or modified.
