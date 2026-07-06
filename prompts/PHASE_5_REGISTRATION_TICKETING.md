# PHASE 5 — Registration & Ticketing

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 0–4 (Event exists and can be published).

## Objective

Let an organizer define ticket types and a custom registration form, and
let an attendee register for a published event through a public flow.
Payment capture itself is Phase 6 — this phase gets an Order to a
"pending payment" state and stops there.

## Backend Tasks

### Ticket Types
- CRUD scoped to an event: name, price (nullable/0 = free), currency,
  quantity available, per-order purchase limit, sales window
  (start/end), visibility (public/hidden — for VIP invite-only links),
  type tag (VIP/Regular/Student/Workshop/Early-bird are just examples of
  the tag value, not separate models).
- Real-time remaining-quantity calculation (available minus
  sold-or-reserved, accounting for a short reservation hold during
  checkout to prevent oversell).

### Dynamic Form Builder
- `RegistrationForm` (tenant, belongs to Event) with an ordered array of
  field definitions (type: text/email/phone/select/checkbox/textarea,
  label, required, options-for-select).
- Server-side validation dynamically built from the field definitions
  when a registration is submitted — do not trust the frontend's
  validation alone.

### Coupons
- Apply-coupon endpoint: validates code, expiry, usage limit, and
  applicability (specific ticket types vs. whole order), returns the
  discounted total without committing the order yet.

### Registration & Waiting List
- `POST /events/{event}/register` — creates a `Registration` +
  `Order` + `OrderItem`(s) in a `pending_payment` status, decrements
  available quantity via a reservation hold (expires after N minutes if
  payment isn't completed — use a scheduled job to release expired
  holds).
- If a ticket type is sold out, offer a `WaitingList` entry instead of
  blocking the registration silently.
- Registration confirmation email queued (uses Phase 0's notification
  strategy) — for this phase, email content can be a simple confirmation
  ("we've received your registration, payment pending"); the
  fully-formatted ticket/QR email comes after Phase 6 and 7 exist.

## Frontend Tasks

- **Organizer side**: `(dashboard)/events/[id]/tickets` — ticket type
  CRUD table; `(dashboard)/events/[id]/form-builder` — drag/reorder field
  builder with live form preview; `(dashboard)/events/[id]/coupons` —
  coupon CRUD.
- **Public side**: `(public)/events/[slug]/register` — multi-step flow:
  select ticket(s) → dynamic form renders based on the organizer's field
  config → coupon code entry → order summary → hands off to Phase 6's
  payment step.

## Validation & Policies

- `TicketTypePolicy`, `RegistrationFormPolicy` scoped to event ownership.
- Public registration endpoint is unauthenticated-friendly (guest
  checkout) but still tenant-scoped correctly to the event's
  organization.
- Oversell prevention must be tested under concurrent requests (a test
  that fires parallel registration attempts against a 1-quantity ticket
  type and asserts only one succeeds).

## Tests

- Ticket type CRUD + visibility rules (hidden ticket only reachable via
  its dedicated link, not the general listing).
- Dynamic form: submitting a registration missing a required custom
  field is rejected server-side.
- Coupon: expired/over-limit/wrong-ticket-type coupon is rejected with a
  clear error; valid coupon reduces the total correctly.
- Oversell race-condition test as described above.
- Waiting list: registering for a sold-out ticket type creates a
  waiting-list entry instead of an order.

## Definition of Done

- An attendee can browse a published event's public page, select a
  ticket, fill the organizer's custom form, apply a coupon, and land on
  an order summary in `pending_payment` status — full round trip.
- List every file created or modified.
