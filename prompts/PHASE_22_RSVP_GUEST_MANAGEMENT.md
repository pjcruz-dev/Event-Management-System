# PHASE 22 — RSVP & Guest Management

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 5 (registration, forms, waiting list), 7 (QR
> check-in, tickets/badges), 10 (public event pages). Phase 11
> (production email templates) is recommended but not blocking — ship
> queued mailables using the same notification patterns as Phase 5 if
> Phase 11 is not yet complete.

## Objective

Add an **invite-first guest management layer** for galas, weddings,
fundraisers, and dinners — comparable to the guest-list / RSVP /
seating slice of products like [RSVPify](https://rsvpify.com/) — **without
replacing** the existing open registration + conference flows.

Organizers must be able to run an event in one of three modes:

| Mode | Behavior |
|------|----------|
| `open` | **Default / current behavior** — public `/e/{slug}/register` |
| `invite_only` | Only pre-invited guests (token link) may register or RSVP |
| `rsvp` | Accept / Decline / Maybe (+ optional plus-ones); free events
  auto-confirm; paid events still create orders per Phase 5/6 rules |

Conference events keep using `open` mode; RSVP features must not break
existing registration, check-in, or dashboard analytics.

**Design principle:** extend `Registration` and related services — do not
build a parallel attendee system. Invited guests who accept become (or
update) a `Registration` record so QR check-in, exports, and analytics
stay unified.

Document the module in `ai-development-kit/02_Backend/RSVP_GUEST_MANAGEMENT.md`.

---

## Backend Tasks

### Event settings — registration mode
- Add `registration_mode` enum on `Event`: `open`, `invite_only`, `rsvp`
  (default `open` for all existing events).
- Add RSVP settings on `Event` (JSON `rsvp_settings` or explicit columns —
  choose the approach that matches existing `theme_config` patterns):
  - `allow_plus_ones` (bool)
  - `max_plus_ones_per_invite` (int, 0–10)
  - `collect_meal_preferences` (bool) — when true, surface dietary presets
    in the RSVP form (maps to `custom_fields` or dedicated columns; state
    which you chose)
  - `response_deadline` (nullable datetime)
- Validate mode transitions: switching `open` → `invite_only` must not
  orphan public registrations already confirmed (allow, but document
  behavior — e.g. existing registrations remain valid).

### Guest invites (`GuestInvite` model)
- Tenant-scoped model: `organization_id`, `event_id`, `email`, `first_name`,
  `last_name` (nullable), `phone` (nullable), `invitation_token` (unique,
  opaque), `status` enum: `pending`, `sent`, `opened`, `responded`,
  `declined`, `expired`, `revoked`.
- Optional: `household_name`, `group_label` (e.g. "Smith family"), `tags`
  (JSON array of strings for segmentation).
- Optional pre-assignment: `table_id` nullable FK (added when seating
  exists — see Seating section).
- `registration_id` nullable FK — set when guest accepts and a
  `Registration` is created/linked.
- `plus_one_limit` nullable int — overrides event default when set.
- Indexes: `(event_id, email)`, unique `invitation_token`, `(event_id,
  status)`.

### Guest invite APIs (organizer, authenticated)
- `GET /api/v1/events/{event}/guest-invites` — paginated, filterable by
  status, tag, search (name/email).
- `POST /api/v1/events/{event}/guest-invites` — single invite.
- `POST /api/v1/events/{event}/guest-invites/import` — CSV upload
  (validate columns: email required; first_name, last_name, phone, tags,
  plus_one_limit, table optional). Return import summary `{ created,
  skipped, errors[] }` — do not fail the whole batch on one bad row.
- `PUT /api/v1/events/{event}/guest-invites/{invite}` — update details.
- `DELETE /api/v1/events/{event}/guest-invites/{invite}` — revoke.
- `POST /api/v1/events/{event}/guest-invites/{invite}/send` — queue
  invitation email (idempotent — resend allowed).
- `POST /api/v1/events/{event}/guest-invites/send-bulk` — send to all
  `pending` or selected IDs.
- Policies: organizer permissions — add `guest_invites.manage` (or reuse
  an existing event-management permission; document in `AUTH.md`).

### Public RSVP APIs (unauthenticated, token-scoped)
- `GET /api/v1/public/rsvp/{token}` — invite details + event summary +
  current response state (no PII of other guests).
- `POST /api/v1/public/rsvp/{token}/respond` — body:
  - `response`: `accepted` | `declined` | `maybe`
  - attendee fields (name, email pre-filled from invite, phone)
  - `plus_ones`: array of `{ first_name, last_name, email? }` when allowed
  - `custom_fields` / meal preferences per event form
  - `ticket_type_id` when event has paid ticket types and response is
    `accepted` (reuse Phase 5 order flow)
- On `accepted`: create or update `Registration` (+ `Order` if paid) via
  existing `CreateRegistrationAction` or a dedicated
  `RespondToGuestInviteAction` that wraps it — **do not duplicate**
  inventory/coupon logic.
- On `declined` / `maybe`: update invite status; create a
  `Registration` with status `cancelled` or a new `RsvpResponse` record —
  **pick one model strategy and document it**; exports must show declined
  headcount.
- Enforce `response_deadline`, revoked/expired tokens (404), and
  plus-one limits server-side.

### Registration extensions
- Add `rsvp_response` enum nullable on `Registration`: `accepted`,
  `declined`, `maybe` (null for normal open registrations).
- Add `guest_invite_id` nullable FK on `Registration`.
- Add `is_plus_one` bool + `primary_registration_id` nullable self-FK for
  plus-one attendees linked to a primary guest.
- Extend `RegistrationStatus` only if needed — prefer `rsvp_response` +
  existing `confirmed`/`cancelled` over proliferating statuses.

### Public register route guard
- When `registration_mode` is `invite_only` or `rsvp`, `GET/POST
  /api/v1/public/events/{slug}/register` must require a valid
  `invitation_token` query param (or reject with 403 + message). `open`
  mode unchanged.

### Seating (tables & assignments)
- `EventTable` (tenant-scoped): `event_id`, `name` (e.g. "Table 12"),
  `capacity`, `sort_order`, `shape` enum (`round`, `rectangle`, `head`),
  optional `x`/`y`/`rotation` for chart canvas (decimals).
- `table_id` nullable on `Registration` and `GuestInvite`.
- Organizer APIs:
  - CRUD tables for an event
  - `PUT /api/v1/events/{event}/seating/assignments` — bulk assign
    registration/invite IDs to tables (validate capacity)
  - `GET /api/v1/events/{event}/seating` — tables + assigned guests
  - Export seating chart CSV/PDF for venue (simple tabular PDF is fine;
    fancy floor-plan rendering is not required)
- Check-in scan response (`CheckInScanResource`) includes `table_name`
  when assigned.
- Ticket PDF (`resources/views/pdf/ticket.blade.php`) includes table when
  assigned.

### Email notifications
- Queued mailables / notifications:
  - `GuestInvitationNotification` — personal link
    `{FRONTEND_URL}/rsvp/{token}`
  - `RsvpReminderNotification` — scheduled or manual bulk send
  - `RsvpResponseConfirmationNotification` — accepted/declined/maybe
- Use existing queue + mail config; HTML templates under
  `resources/views/mail/` (branded minimally if Phase 11 templates not
  ready).

### Analytics & exports
- Extend event dashboard summary with RSVP metrics: invited, sent,
  accepted, declined, maybe, response rate, plus-one count.
- Extend `ReportExportService` with `guest-invites` and `seating` export
  types (CSV/XLSX streamed).

---

## Frontend Tasks

### Organizer — Guest list (`/events/[id]/guests`)
- Guest list table: name, email, status, RSVP response, table, tags,
  plus-ones, registration #, sent at, responded at.
- Filters: status, tag, response type, table, search.
- Actions: add guest, edit, revoke, send/resend invite, bulk send,
  import CSV (with template download link).
- RSVP settings panel on event edit (Settings tab or dedicated sub-nav):
  registration mode, plus-one rules, response deadline.

### Organizer — Seating chart (`/events/[id]/seating`)
- Drag-and-drop canvas: drag **unassigned** guests onto tables (use
  `@dnd-kit` — already in project for landing page builder).
- Table editor: add/rename/delete tables, set capacity.
- List view fallback for accessibility (assign via dropdown without drag).
- Show capacity warnings when over-assigning.

### Public — RSVP page (`(public)/rsvp/[token]/page.tsx`)
- SSR-friendly page: event name, date, venue, host message.
- Response buttons: Accept / Decline / Maybe (hide Maybe if disabled in
  settings).
- Plus-one sub-form when allowed.
- Meal/dietary fields when enabled.
- Ticket selection when paid + accepted (embed existing register step
  components where possible).
- Confirmation screen with calendar-add link optional (nice-to-have).

### Public event nav
- When mode is `invite_only`/`rsvp`, hide or disable public Register CTA
  unless user arrived via invite token (carry `?invitation_token=` through
  nav links).

### Check-in UI
- Show **Table {name}** on successful scan when assigned.

---

## Integration Points (do not break)

| Existing feature | Integration |
|------------------|-------------|
| Phase 5 register | Wrapped by RSVP accept; same Order/Registration path |
| Phase 7 QR check-in | Same `Registration` QR; add table to scan UI |
| Phase 9 exports | New export types; existing registration export unchanged |
| Phase 10 public pages | Register CTA respects `registration_mode` |
| Org invitations (Phase 1) | Unrelated — do not confuse team invites with guest invites |

---

## Explicitly Out Of Scope

- Full CRM / contact database across events (defer to Phase 12 enterprise)
- Salesforce, HubSpot, Zapier integrations
- Native iOS/Android guest-list app (Phase 21 PWA is sufficient for MVP)
- Appointment / slot-based scheduling (1:1 bookings)
- Donation line items at checkout
- Upload-your-own invitation **design editor** (use email HTML template +
  event branding; custom image upload in email body is optional, not a
  Canva clone)
- Household RSVP where one form submits for 10+ named guests without
  plus-one structure — use group tags + individual invites instead
- Multi-language RSVP (Phase 16)

---

## Tests

### Backend (PHPUnit)
- `open` mode: existing public register flow unchanged (regression).
- `invite_only`: public register without token → 403; with valid token →
  success.
- Invite CSV import: valid rows created, invalid rows reported, no
  partial tenant leak.
- RSVP respond `accepted` creates `Registration` + QR token (free event).
- RSVP respond `declined` does not create check-in-eligible registration.
- Plus-one limit enforced server-side.
- Revoked/expired token → 404 on public RSVP.
- Seating: cannot assign beyond table capacity.
- Check-in scan returns `table_name` when set.
- Tenant isolation: org A cannot read org B guest invites.

### Frontend
- Guest list page renders, send invite calls API (smoke via component
  tests optional; manual DoD acceptable if backend coverage is strong).

---

## Definition of Done

- An organizer can set an event to `rsvp` mode, import or add guests, send
  invitations, and see responses in a guest list dashboard.
- An invitee can open `/rsvp/{token}`, accept/decline/maybe (+ plus-ones),
  and receive confirmation; accepted free guests get QR check-in like any
  other registration.
- Organizer can assign guests to tables via drag-and-drop (or list
  fallback), export seating, and see table on check-in scan + ticket PDF.
- `open` mode events behave exactly as before Phase 22.
- `ai-development-kit/02_Backend/RSVP_GUEST_MANAGEMENT.md` documents modes,
  models, token security, and CSV import format.
- `FEATURES.md` and `ROADMAP.md` updated; list every file created or
  modified.

---

## Suggested Build Order (within this phase)

1. `registration_mode` + `GuestInvite` model + organizer CRUD APIs
2. Public `/rsvp/{token}` respond flow (accept/decline, free events)
3. Guest list UI + CSV import + send invitation email
4. Plus-ones + `invite_only` guard on public register
5. `EventTable` + assignments + check-in/ticket table display
6. Seating chart UI + exports + dashboard RSVP metrics

Stop and flag if Phase 5 registration action cannot be extended cleanly —
do not fork duplicate order logic.
