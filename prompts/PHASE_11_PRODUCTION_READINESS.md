# PHASE 11 — Production Readiness

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: all prior phases functioning. This phase hardens, it does
> not add business features.

## Objective

Take the application from "feature-complete" to "safe to run in
production": queues on Redis, real notification delivery, monitoring,
security hardening, deployment automation, and a test suite that
actually gets run in CI.

## Backend Tasks

### Redis & Queues
- Move queue connection (Phase 0 stub) to Redis in all non-local
  environments; configure `horizon` (recommended) or documented
  supervisor config for worker process management.
- Verify every queued job from earlier phases (confirmation emails,
  exports, certificate generation, expired-hold release, post-event
  certificate trigger) actually has `ShouldQueue` applied and a sane
  retry/backoff policy, not left as synchronous by accident.

### Scheduler
- Register the scheduled jobs referenced in earlier phases (expired
  reservation-hold release from Phase 5/6, post-event certificate
  generation from Phase 7) in Laravel's scheduler, with documented cron
  entry for the production crontab.

### Notifications & Email Templates
- Move from placeholder email content (used during earlier phases) to
  final branded templates for: registration confirmation, payment
  receipt, invitation, password reset, event reminder.
- Confirm every notification channel used (mail, database) is
  intentional per notification type, not left as a default guess.

### Storage & Cloud Uploads
- Confirm production `filesystems.php` disk actually points at S3 (or
  chosen provider), avatar/logo/badge/certificate uploads tested against
  it, not silently still writing to local disk in a prod-like
  environment.

### Logging & Monitoring
- Confirm the dedicated log channels from Phase 0 (`security`,
  `payments`, `audit`) are wired to a real destination appropriate for
  production (e.g., stderr/stdout for container log collection, or a
  documented external sink) rather than local files that vanish on
  container restart.
- Add basic application monitoring hooks (error tracking service
  integration point — document where to plug in Sentry or equivalent,
  implement if credentials are available, otherwise leave a clearly
  marked, documented integration point rather than a fake TODO).

### Security Hardening
- Rate limiting on auth endpoints, public registration, and webhook
  endpoints (separate, appropriate limits per endpoint class).
- Security headers (CSP, HSTS, X-Frame-Options, etc.) configured at the
  Next.js and/or reverse-proxy level.
- Review every previous phase's file upload handling for MIME-type and
  size validation (avatar, logo, materials, attachments) — this phase
  should audit and tighten, not just assume earlier phases got it right.

### Caching
- Confirm the `CacheService` tenant-prefixing (Phase 0/2) is actually
  backed by Redis in production, and that dashboard metric caching
  (Phase 9) has correct invalidation, not stale data silently served
  indefinitely.

### Feature Flags & Subscription Plans
- A simple feature-flag mechanism (config or DB-backed) so plan-gated
  features (e.g., custom domain, advanced analytics) can be toggled per
  organization based on their subscription tier.
- `SubscriptionPlan` model + `Organization.plan_id`, enforced at the
  policy layer for plan-gated actions — this phase establishes the
  mechanism; deep billing integration (charging for plans) is out of
  scope unless explicitly requested, since it overlaps with Phase 6's
  payment gateways and should be scoped deliberately.

### Testing & CI
- Ensure the full test suite (accumulated across all phases) passes.
- Add a CI config (GitHub Actions, since the stack already uses GitHub)
  that runs backend tests, frontend type-checking/lint, and a build
  check on every PR.

### Deployment
- `Dockerfile`s for backend and frontend, `docker-compose.yml` for local
  parity (app, MySQL, Redis).
- `DEPLOYMENT.md` covering the target deployment approach (document
  whichever is intended — e.g., containers on a VPS, or a managed
  platform), environment variable checklist, and the migration/seeding
  steps for a fresh production deploy.
- `ENVIRONMENT.md` — every required env var across both apps, with a
  one-line description and whether it's secret.
- `BACKUPS.md` — database backup approach and restore procedure.

## Definition of Done

- CI runs green on a fresh clone.
- `docker-compose up` brings up a working local stack matching
  production topology.
- Every checklist item above is either implemented or explicitly logged
  as a follow-up in `ROADMAP.md` with a reason it was deferred — nothing
  silently skipped.
- List every file created or modified.
