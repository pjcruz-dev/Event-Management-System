# DASHBOARDS.md

## Charting

Recharts, paired with shadcn/ui/Tailwind for consistent styling. One
chart wrapper component handling loading/empty/error state and
dark-mode-aware colors, reused by every chart rather than each chart
reimplementing those states.

## Data Fetching

TanStack Query for all dashboard data. Sensible `staleTime` per metric
type — near-real-time data (check-in rate during a live event) polls
more aggressively than slow-moving data (revenue trend), configured
per-query rather than one global default for everything.

## Screens

- `(dashboard)/events/[id]/analytics` — per-event: revenue trend,
  registrations trend, check-in rate gauge, top ticket types, geographic
  breakdown.
- `(dashboard)/organization/analytics` — cross-event rollup, all of the
  org's events side by side.
- `(dashboard)/organization/activity` — activity/audit log viewer with
  filters (actor, action type, date range).

## Exports From The UI

Export buttons trigger a background job (Phase 9's `REPORTS.md` covers
the backend side) — the UI shows "preparing your export, we'll notify
you," never blocks the main thread waiting on a large synchronous
download.

## Mobile Density

This is the most visually dense screen class in the product. On mobile:
collapse to key metrics + a "view details" link rather than rendering
every chart at full size. Test on an actual narrow viewport, not just a
resized desktop browser window.

## Dark Mode

Charts specifically need verification here — a color scheme tuned for
light mode often loses contrast or looks garish in dark mode; each chart
component pulls colors from the design system's semantic tokens
(`DESIGN_SYSTEM.md`), never hardcoded hex values per chart.
