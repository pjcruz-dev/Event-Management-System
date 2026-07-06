# PHASE 10 — Public Website & Discovery

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 4 (published events with theme/landing config exist).

## Objective

Build the public-facing discovery layer: a searchable event directory,
SEO-optimized event pages, and the performance/rendering strategy that
makes the public site fast and indexable.

## Backend Tasks

### Search & Discovery
- `GET /api/v1/discover/events` — public, unauthenticated, paginated,
  filterable by category/date-range/location/free-vs-paid/keyword.
  Consider whether a dedicated search index (e.g., Laravel Scout with a
  simple driver) is warranted given expected data volume — for now, a
  well-indexed SQL query is acceptable; document the upgrade path in
  `FEATURES.md` or a note in this phase's summary rather than
  over-building.
- Event categories/tags — add a lightweight `EventCategory` model or a
  simple `category` string/enum field on `Event`, whichever fits the
  existing schema better (state which you chose and why).

### Reviews
- Public read endpoint for an event's approved reviews; review
  submission tied to a `Registration` (only attendees can review,
  optionally only after the event has occurred).

### SEO Infrastructure
- Server-rendered (not client-only) meta tags per event page using
  Next.js App Router metadata API, pulling from the event's
  `meta_title`/`meta_description`/`og_image` (Phase 4).
- `sitemap.xml` and `robots.txt` generated dynamically, including all
  published public events.
- Structured data (JSON-LD `Event` schema) on event pages for rich
  search results.

## Frontend Tasks

- `(public)/discover` — event directory with search bar, filter sidebar,
  category chips, pagination or infinite scroll.
- `(public)/events/[slug]` — the public event page rendering the
  organizer's theme/landing-page-builder config from Phase 4, via SSR or
  ISR (Incremental Static Regeneration — revalidate on publish/update
  rather than fully static, since organizers edit content after
  publishing).
- `(public)/organizations/[slug]` — a lightweight public organizer
  profile page listing their public events (only if organizations opt
  into a public profile — respect a privacy setting).
- FAQ block support within the landing page builder's block types
  (extends Phase 4's block list if not already present).
- Recommendation strip ("similar events") on the event page — a simple
  heuristic (same category, nearby date) is sufficient; do not build a
  ML model here, that's Phase 12 territory (AI recommendation engine),
  not this phase.

## Performance & Accessibility

- Public pages must pass basic Core Web Vitals sanity (no obvious
  render-blocking issues, images use Next.js `Image` component with
  proper sizing, lazy-load below-the-fold blocks).
- Full responsive behavior down to small mobile widths, and the
  accessibility basics from the master prompt apply with extra care here
  since this is the highest-traffic, least-controlled-audience surface.

## Tests

- Draft/unpublished/archived events never appear in `/discover` or are
  reachable via direct slug URL (404 or appropriate status, not a leak).
- Sitemap only includes published public events, correctly excludes
  private/unlisted events.
- SEO meta tags render server-side (verifiable via a raw HTML fetch, not
  just checking client-side DOM after hydration).

## Definition of Done

- A published public event is discoverable via `/discover`, has correct
  SEO meta tags and structured data verifiable in raw page source, and
  renders the organizer's custom landing page/theme correctly on mobile
  and desktop, light and dark mode.
- List every file created or modified.
