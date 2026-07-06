# PHASE 4 — Event Management Module

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 0–3 (Event model, tenancy, auth already exist).

## Objective

Build full CRUD and lifecycle management for Events, plus the landing
page/theme builder that lets an organizer customize an event's public
page without code.

## Backend Tasks

### Event CRUD & Lifecycle
- Standard REST endpoints: list (paginated, filterable by status/date),
  show, create, update, delete (soft delete).
- Lifecycle actions as dedicated endpoints, not overloaded PATCHes:
  `POST /events/{event}/publish`, `/archive`, `/duplicate`.
- `PublishEventAction` — validates the event has the minimum required
  fields (name, date, at least one ticket type will exist later — for now
  just name/date/venue) before allowing publish.
- `DuplicateEventAction` — deep-clones an event including its theme and
  landing page config, but resets status to draft and clears dates.

### Slug & SEO
- Auto-generate a unique slug from the event name on create; allow manual
  override; enforce uniqueness per-organization (not globally, unless
  custom domains are used — see below).
- `meta_title`, `meta_description`, `og_image` fields for SEO, editable
  via the landing page builder.

### Custom Domain (structure only)
- A `custom_domain` field and a verification-status enum
  (`unverified|pending|verified`). Full DNS verification flow can be a
  documented TODO for Phase 11 — this phase just needs the schema/UI
  hook, not a working DNS checker.

### Theme & Landing Page Builder (backend)
- `theme_config` JSON schema: primary/secondary color, font choice,
  logo, hero image, layout variant.
- `landing_page_config` JSON schema: ordered array of content blocks
  (hero, about, agenda-preview, speakers-preview, sponsors, FAQ, CTA),
  each block with its own settings object.
- Endpoint to save the builder state; validate the JSON shape server-side
  against a defined Zod-equivalent (Laravel: a custom Rule class) so a
  malformed frontend payload can't corrupt the config.

## Frontend Tasks

- `(dashboard)/events` — list view with status badges, search, filter by
  status/date range.
- `(dashboard)/events/[id]/edit` — tabbed editor: Details, Theme,
  Landing Page, Settings (visibility, capacity, timezone).
- **Landing Page Builder UI** — drag-and-drop block reordering (block
  list with up/down or drag handles is acceptable; full drag-and-drop
  library optional but recommended given shadcn/ui + dnd-kit pairs well),
  live preview pane.
- **Theme Builder UI** — color pickers, font selector, logo/hero upload
  (via Phase 0's storage service), instantly reflected in the preview
  pane.
- Publish/Archive/Duplicate actions surfaced as buttons with confirm
  dialogs, disabled with a tooltip explaining why if publish
  prerequisites aren't met.

## Validation & Policies

- `EventPolicy` — create requires `events.create` permission; update/
  delete/publish require ownership within the org or `events.manage`;
  members with only `events.view` cannot mutate.
- Form Requests for create/update covering all fields including nested
  theme/landing page JSON validation.

## Tests

- Feature tests: create → publish → appears in public listing;
  create → cannot publish without required fields; duplicate produces a
  draft copy with independent theme config (mutating the copy doesn't
  affect the original); tenant isolation (Org A cannot edit Org B's
  event, confirmed via 403/404).

## Definition of Done

- An organizer can create an event, customize its theme and landing page
  through the actual UI, publish it, and see it change status — full
  round trip, not just API-level.
- List every file created or modified.
