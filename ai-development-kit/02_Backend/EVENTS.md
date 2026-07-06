# EVENTS.md

(Laravel Events/Listeners — not to be confused with the `Event` model,
which is the calendar-event business entity. Named `EVENTS.md` per the
doc-kit convention; read "event" here as "domain event.")

## When To Use A Domain Event Vs A Direct Service Call

Use a Laravel Event when:
- Multiple, potentially-growing side effects should happen off one
  trigger (e.g. an order being marked paid should queue a confirmation
  email, generate an invoice, and later phases add more listeners
  without touching the payment code).
- The side effect belongs conceptually to a different domain than the
  trigger (payment confirmation triggering ticket generation is a
  cross-domain concern).

Use a direct Service/Action call when:
- There's exactly one caller and one well-defined next step — an event
  adds indirection without adding value.

## Key Domain Events

| Event | Fired When | Listeners |
|---|---|---|
| `OrderPaid` | Payment webhook confirms payment (Phase 6) | Generate invoice, queue confirmation email, (Phase 7) generate QR ticket |
| `RegistrationCheckedIn` | Successful scan (Phase 7) | Log activity, update live check-in dashboard cache |
| `EventPublished` | `PublishEventAction` succeeds (Phase 4) | Invalidate discovery cache, generate sitemap entry (Phase 10) |
| `ReservationHoldExpired` | Scheduled job releases a hold (Phase 5/6) | Release ticket quantity back to pool |
| `RefundProcessed` | Organizer-initiated refund completes (Phase 6) | Generate credit note, log to `payments.log` |

## Conventions

- Event classes live under `app/Events/`, named as past-tense facts
  (`OrderPaid`, not `PayOrder` — that's an Action).
- Listeners live under `app/Listeners/`, one responsibility each; queued
  listeners (`ShouldQueue`) for anything that isn't instant (emails,
  PDF generation).
- Events carry the minimal data needed (usually just the relevant
  model/ID), not a sprawling payload — listeners re-fetch what they need.
- Do not use events as a substitute for the Service/Action layer's normal
  synchronous return flow within a single request/response cycle.
