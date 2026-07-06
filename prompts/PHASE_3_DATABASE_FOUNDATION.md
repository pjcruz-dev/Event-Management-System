# PHASE 3 — Database Foundation

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 0, 1, 2 (apply `BelongsToTenant` from Phase 2 to every
> tenant-owned model below).

## Objective

Generate the full data model for the platform: migrations, Eloquent
models with relationships, factories, and seeders. No business logic,
controllers, or endpoints yet — this phase is the schema and the ORM
layer only.

## Models To Create

Apply `BelongsToTenant` to every model marked **(tenant)**.

**Core**
- `Event` (tenant) — name, slug, description, venue, timezone, capacity,
  status (draft/published/archived), visibility (public/private), theme
  config JSON, landing page config JSON, custom domain, dates.
- `EventSetting` (tenant) — per-event key/value config.

**Sessions & People**
- `Track` (tenant), `Session` (tenant) — belongs to Event and Track,
  room, start/end time, capacity.
- `Speaker` (tenant) — bio, photo, social links; pivot to Sessions.
- `Sponsor` (tenant), `Exhibitor` (tenant), `Booth` (tenant).

**Registration & Commerce**
- `TicketType` (tenant) — name, price, quantity, sales window, visibility.
- `Coupon` (tenant) — code, discount type/value, usage limits, validity
  window.
- `Registration` (tenant) — attendee info, links User (nullable, for
  guest checkout) to Event.
- `Order` (tenant), `OrderItem` — links Registration/TicketType to a
  payment.
- `Invoice` (tenant).
- `Review` (tenant) — attendee feedback on an Event.
- `Certificate` (tenant) — issued to a Registration on completion.

**System / Cross-Cutting (not necessarily tenant-scoped)**
- `ActivityLog` (tenant) — actor, action, subject, metadata JSON.
- `Attachment` / `Media` (tenant) — polymorphic, links to Storage
  strategy from Phase 0.
- `Notification` — Laravel's default notifications table.
- Queue/job tables (`jobs`, `failed_jobs`) via standard Laravel
  migrations.

## Migration Requirements

- Every foreign key has an explicit index and an `onDelete` behavior
  (cascade for owned children, restrict/null for shared references —
  state which you chose and why in a comment).
- Every tenant-owned table has an index on `organization_id`, and a
  composite index on `(organization_id, <commonly filtered column>)`
  where there's an obvious hot path (e.g., `(organization_id, status)` on
  `events`).
- Soft deletes on models where "undo" or audit history matters (Event,
  Order, Registration, Organization-adjacent records) — not applied
  blindly everywhere.
- JSON columns (`theme_config`, `landing_page_config`, `settings`, `metadata`)
  documented with their expected shape in a code comment above the
  migration column.

## Relationships

- Write every relationship explicitly on the model (no relying on magic
  method names) — `hasMany`, `belongsTo`, `belongsToMany` with pivot
  models where the pivot carries data (e.g., `EventSpeaker` pivot if it
  needs a `role` like "keynote").
- Add `$casts` for every JSON, date, decimal, and enum column.

## Factories & Seeders

- A factory for every model above, with realistic fake data (use
  `fake()->` helpers appropriately — real venue-sounding names, not
  `Str::random()`).
- A `DatabaseSeeder` that produces: 3 organizations, a handful of users
  per org with varied roles, 2–4 events per org in different statuses,
  sessions/speakers/sponsors for at least one fully "realistic" event,
  ticket types with a mix of paid/free, and a batch of registrations and
  orders so dashboards (built in later phases) have something to show.

## Definition of Done

- `php artisan migrate:fresh --seed` runs cleanly with zero errors.
- Every tenant-owned table is confirmed to respect `BelongsToTenant`
  (quick sanity: querying as one seeded org never returns another org's
  rows).
- `DATABASE_GUIDELINES.md` documents the schema at a glance: an ER
  overview (can be a Mermaid diagram in the markdown), and the rules
  above (indexing, soft deletes, JSON column shapes).
- List every migration, model, factory, and seeder file created.
