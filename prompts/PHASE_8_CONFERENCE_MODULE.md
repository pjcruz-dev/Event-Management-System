# PHASE 8 — Conference Module

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 3–7 (Track/Session/Speaker/Sponsor/Exhibitor/Booth
> models already exist from Phase 3; check-in exists from Phase 7 for
> session-level attendance).

## Objective

Turn the flat Event into a full multi-track conference: agenda, speaker
management, sponsor/exhibitor portals, and lead capture for exhibitors.

## Backend Tasks

### Agenda / Sessions / Tracks
- CRUD for `Track` and `Session`, with `Session` requiring a track, room,
  start/end time, and optional capacity.
- Conflict detection: warn (not block) when a speaker is assigned to two
  overlapping sessions, or a room is double-booked.
- `SessionRegistration` — attendees can optionally pre-select sessions
  they plan to attend (for capacity-limited workshops); enforce capacity
  the same way ticket oversell was prevented in Phase 5 (reservation +
  race-condition-safe).

### Speakers
- CRUD with bio, headshot, social links, and a many-to-many pivot to
  Sessions (a speaker can co-present); a public speaker profile page.

### Sponsors, Exhibitors, Booths
- `Sponsor` CRUD with tiers (e.g., platinum/gold/silver — store as a
  simple string/enum tied to the event, not a rigid global list).
- `Exhibitor` CRUD, each optionally linked to a `Booth` (booth number/
  location within the venue).
- **Exhibitor Portal**: a scoped-access area where an exhibitor contact
  (invited via email, not necessarily a full organization member) can
  edit their own company profile, upload materials, and view their
  booth's lead-scan results — this needs its own lightweight
  authorization: exhibitor users belong to an `Exhibitor`, not to the
  organizing team, and their access is limited to their own booth's data
  only.

### Lead Scanner
- Reuses the QR infrastructure from Phase 7 but scoped to exhibitor
  context: exhibitor staff scan attendee badges to capture a lead
  (`ExhibitorLead`: exhibitor_id, registration_id, scanned_by, notes,
  timestamp). Exhibitors only see leads captured at their own booth.

### Conference Analytics (module-local; full dashboards are Phase 9)
- Per-session attendance count (from `SessionRegistration` +
  Phase 7 check-in data where sessions have their own gate scanning).
- Per-sponsor/exhibitor lead count.

## Frontend Tasks

- `(dashboard)/events/[id]/agenda` — track/session builder with a
  timeline or grid view, conflict warnings surfaced inline.
- `(dashboard)/events/[id]/speakers`, `/sponsors`, `/exhibitors` — CRUD
  screens.
- `(public)/events/[slug]/agenda` — public-facing agenda grid, filterable
  by track/day, with session detail modal (speaker bios, room, time).
- **Exhibitor Portal** (separate, lighter-weight authenticated area,
  e.g. `(exhibitor-portal)/...`) — profile editor, lead list with export.
- Lead scanner reusing Phase 7's scanner component, parameterized for
  exhibitor-lead mode instead of general check-in mode.

## Authorization

- New `ExhibitorPolicy`/guard distinguishing exhibitor-portal users from
  organization members — they authenticate but have a narrower token
  scope (their own exhibitor record only). Document this as a distinct
  guard in `TENANCY.md`'s follow-up notes or a new section here.

## Tests

- Session capacity + conflict detection.
- Exhibitor user cannot see another exhibitor's leads or edit another
  exhibitor's profile, even within the same event.
- Public agenda only shows published sessions of a published event.

## Definition of Done

- An organizer can build a multi-track agenda, assign speakers, add
  sponsors/exhibitors with booths, and an exhibitor contact can log into
  their own portal and scan a lead — full round trip.
- List every file created or modified.
