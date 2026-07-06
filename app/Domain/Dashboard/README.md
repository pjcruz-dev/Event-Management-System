# Dashboard

Organizer analytics, exports, and activity feeds (Phase 9).

## Services

| Service | Responsibility |
|---|---|
| `DashboardMetricsService` | Per-event and per-organization metric aggregation |
| `ActivityFeedService` | Paginated, filterable `ActivityLog` queries |
| `ReportExportService` | Streamed CSV/XLSX exports, PDF summary, async queue metadata |

## API routes

- `GET /api/v1/events/{event}/analytics`
- `GET /api/v1/organization/analytics`
- `GET /api/v1/organization/activity`
- `GET /api/v1/events/{event}/activity`
- `GET|POST /api/v1/events/{event}/exports/{type}` (`reports.export`)
- `GET /api/v1/exports/{exportId}` + `/download`

## Frontend

- `/events/[id]/analytics`
- `/organization/analytics`
- `/organization/activity`

See `ai-development-kit/02_Backend/REPORTS.md` for metrics, caching, and export delivery.
