# PHASE 20 — Full Observability

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 11 (left a documented integration *point* for error
> tracking — this phase actually wires up tracing, alerting, and uptime
> monitoring, not just error capture).

## Objective

Phase 11 said "implement if credentials are available, otherwise leave a
documented integration point." This phase assumes credentials are now
available and completes the job: real error tracking, request tracing,
uptime monitoring, and alerting rules — so an incident is caught by a
monitor, not by an organizer's support email.

## Backend Tasks

### Error Tracking
- Wire the Phase 11 integration point to a real error-tracking service
  (Sentry or equivalent) for both the Laravel backend and Next.js
  frontend. Capture: unhandled exceptions, failed queue jobs (Phase 0/11's
  `failed_jobs`), and webhook signature-verification failures (these are
  security-relevant, not just bugs — see `SECURITY.md`).
- Scrub PII and secrets from error payloads before they leave the
  application (no raw request bodies containing passwords, tokens, or
  payment payloads sent to a third-party error tracker unfiltered).

### Distributed Tracing
- Request-level tracing across the Next.js → Laravel → MySQL/Redis/
  external-gateway call chain, correlating a single user-facing request
  with its downstream calls, so a slow page load can be traced to its
  actual bottleneck (slow query vs. slow gateway call vs. slow queue
  wait) rather than guessed at.

### Uptime & Synthetic Monitoring
- External uptime checks (not self-reported — a service outside the
  infrastructure hitting the app) against: the public homepage, the API
  health-check endpoint, and the checkout flow specifically (a synthetic
  transaction, since "the homepage is up" doesn't guarantee payments
  work).

### Alerting Rules
- Defined, documented thresholds — not "alert on every error," which
  trains everyone to ignore alerts:
  - Error rate above a defined threshold over a rolling window.
  - Queue backlog (any queue) above a defined depth for longer than a
    defined duration.
  - Uptime check failure (immediate alert, no threshold — downtime is
    always urgent).
  - Payment webhook failure rate above a defined threshold (distinct from
    general error rate, since this is revenue-affecting).
  - Sustained notification/email delivery failure rate (Phase 11's
    `NOTIFICATIONS.md` delivery-failure logging, now with an actual
    alert on top of the log).
- Alerts route to a real on-call destination (email/SMS/chat webhook —
  whatever the team actually monitors), not just written to a log no one
  watches.

### Dashboards
- An internal operational dashboard (can be the monitoring provider's
  built-in dashboarding, doesn't need to be custom-built) surfacing:
  error rate, p50/p95/p99 latency, queue depths, active user count — the
  "is the system healthy right now" view for the team, distinct from the
  organizer-facing analytics dashboard from Phase 9.

## Tests

- A deliberately-triggered error (e.g. a test exception route, gated
  behind an env flag so it doesn't exist in production) confirms it
  reaches the error tracker with PII scrubbed.
- A deliberately-failed queue job confirms it's both in `failed_jobs` and
  visible in the monitoring dashboard.
- Uptime check configuration is verified against a real (temporary)
  simulated outage in staging, confirming the alert actually fires and
  reaches the on-call destination.

## Definition of Done

- Error tracking, tracing, uptime monitoring, and alerting are all live
  against the real production (or production-mirroring staging)
  environment, not just configured-but-unverified.
- `OBSERVABILITY.md` documents every alert rule, its threshold, and where
  it routes — so a new team member can understand what "healthy" means
  for this system without archaeology.
- List every file created or modified.
