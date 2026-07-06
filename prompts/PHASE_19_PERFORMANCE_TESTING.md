# PHASE 19 — Load & Performance Testing

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 11 (production infra — Redis queues, real deployment
> topology — must exist; load-testing a dev-mode sync-queue setup gives
> meaningless numbers).

## Objective

Validate the system under the specific failure mode event platforms are
known for: a ticket-sale going live and every attendee hitting checkout
in the same 30 seconds. Phase 5 built oversell-safe reservation holds and
Phase 10 covered basic Core Web Vitals sanity — this phase proves both
hold under real concurrent load, not just a sequential test.

## Tooling

- k6 (or Artillery — pick one, document the choice) for HTTP load
  generation, scriptable, CI-runnable.
- Target a staging environment that mirrors production topology
  (Phase 11's `docker-compose` / real deployment, not local `sync` queues)
  — load numbers from a dev environment are not representative.

## Test Scenarios

### Registration Spike ("ticket drop")
- Simulate N concurrent users hitting `POST /events/{event}/register` for
  a limited-quantity ticket type at the same moment.
- Assert: exact oversell prevention (quantity sold never exceeds
  available, re-verifying Phase 5's race-condition test but at realistic
  concurrency, e.g. hundreds-to-low-thousands of simultaneous requests
  depending on target scale) and a bounded, acceptable p95/p99 response
  time under load (define the target number for this project, e.g.
  "p95 under 2s at N concurrent users" — pick a number appropriate to
  expected real usage and record it here).

### Checkout / Payment Webhook Burst
- Simulate a burst of webhook deliveries (payment confirmations) arriving
  in a short window, confirming the `webhooks` queue (Phase 11) drains
  without unbounded backlog growth and without dropping any events.

### Check-In Scanner Burst
- Simulate many simultaneous check-in scans (venue-entrance rush),
  confirming Phase 7's duplicate-prevention logic holds under concurrency
  and the scan endpoint's response time stays usable for staff (a slow
  scan response creates a physical line at the door).

### Public Discovery / Dashboard Read Load
- Baseline read-heavy load test against `(public)/discover` (Phase 10)
  and the organizer analytics dashboard (Phase 9), confirming caching
  (Phase 0/9's `CacheService`) is actually reducing DB load under
  concurrent reads, not just present but ineffective.

## What "Pass" Means

- Zero oversold tickets at any tested concurrency level — this is a hard
  requirement, not a "mostly fine" metric.
- No 5xx errors under the target load level (errors under load beyond the
  target ceiling should degrade gracefully — e.g. clear "high demand,
  please wait" messaging — not silently 500).
- Queue backlogs (webhooks, exports) drain within a defined, documented
  time bound after a burst, not grow unbounded.
- Response-time targets defined per scenario are met at the target
  concurrency level.

## Deliverables

- `k6/` (or `artillery/`) scripts checked into the repo, runnable
  on-demand against staging (not run automatically on every PR — this is
  a periodic/pre-launch check, not a CI gate, since it generates real
  load).
- `PERFORMANCE.md` — documents the scenarios above, the target numbers
  chosen for this project, actual results from the most recent run, and
  any bottleneck found + how it was addressed (e.g. "found the discovery
  query missing a composite index under load, added it, re-ran, passed").

## Definition of Done

- Every scenario above has been run against a production-topology staging
  environment at least once, with results recorded in `PERFORMANCE.md`.
- Any failure found (oversell, dropped webhook, unbounded queue growth,
  missing index) is fixed and re-verified, not just logged as a known
  issue.
- List every file created or modified, including any fix made to earlier-
  phase code as a result of what load testing surfaced.
