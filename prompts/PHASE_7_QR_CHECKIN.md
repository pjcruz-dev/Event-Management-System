# PHASE 7 — QR Check-In System

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 6 (a Registration reaches `paid` status).

## Objective

Generate a scannable ticket for every paid registration, and build the
on-site scanner flow organizers/staff use to check attendees in,
including offline resilience for venues with poor connectivity.

## Backend Tasks

### QR & Ticket Generation
- On `Order` reaching `paid`, generate a unique, non-guessable QR payload
  per `Registration` (signed token, not just the raw registration id) and
  store it on the record.
- `TicketPdfService` — generates a PDF ticket per registration (QR code +
  event details + attendee name), attached to the confirmation email
  queued back in Phase 6.
- `CertificateService` — generates a completion certificate PDF,
  triggered manually by the organizer or automatically post-event (a
  scheduled job checking event end-date), templated per event.

### Check-In
- `POST /checkin/scan` — accepts the QR token, validates signature and
  registration status, and:
  - Rejects if already checked in (returns who/when it was checked in,
    for staff visibility — this is the duplicate-prevention requirement).
  - Rejects if the registration isn't `paid`.
  - Records `Registration.checked_in_at`, `checked_in_by` (staff user),
    `device_id`, `gate` (which entrance/session gate scanned it), and
    optionally GPS coordinates if the client provides them.
  - Flags VIP ticket types distinctly in the scan response so staff UI
    can visually highlight it.
- `ActivityLog` entry on every scan attempt, success or duplicate/reject,
  for later audit.

### Offline Support (backend contract)
- `POST /checkin/sync-batch` — accepts an array of scan events performed
  while offline (each with a client-generated timestamp and idempotency
  key), processes them in order, and returns per-item results so the
  client can reconcile which ones succeeded/conflicted (e.g., two staff
  scanned the same attendee at two different offline gates — first
  timestamp wins, the other is marked duplicate).

### Badge Printing
- `BadgeService` — generates a print-ready badge (PDF or image, ~4x6 or
  a documented standard size) with attendee name, org/title if collected,
  QR code, and event branding pulled from the event's theme config.

## Frontend Tasks

- `(dashboard)/events/[id]/checkin` — scanner page using the device
  camera (a well-supported browser QR library), showing scan result
  (success/duplicate/invalid) with clear color-coded feedback and an
  audible/haptic cue where feasible.
- Offline mode: detect connectivity loss, queue scans in IndexedDB or
  similar local storage, show a "N scans pending sync" indicator, and
  auto-flush via `sync-batch` when connectivity returns.
- Badge print view — a printable page/PDF trigger from the check-in
  screen for on-site badge printing.
- `(dashboard)/events/[id]/checkin/activity` — live feed of check-ins
  (who, when, gate) for organizers monitoring from a back office.

## Tests

- Valid scan checks in a paid registration exactly once; a second scan
  of the same QR is rejected as duplicate with the original check-in
  info returned.
- Scan of an unpaid/cancelled registration is rejected.
- Tampered/invalid QR signature is rejected without leaking which part
  was wrong.
- Batch sync: two offline scans of the same registration from different
  devices resolves deterministically (earliest timestamp wins, other
  marked duplicate) with no double-check-in state.

## Definition of Done

- A paid registration's PDF ticket QR can be scanned through the actual
  scanner UI and marked checked in, with duplicate scans correctly
  rejected.
- Offline queue → reconnect → sync flow demonstrably works (can be shown
  via a test or a manual walkthrough script in the PR description).
- List every file created or modified.
