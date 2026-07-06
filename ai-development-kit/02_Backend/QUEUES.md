# QUEUES.md

## Connections

| Environment | Driver |
|---|---|
| local | `sync` or `database` (fast feedback, no Redis dependency for a solo dev loop) |
| staging/production | `redis`, managed via Laravel Horizon |

## Queues By Job Type

| Job | Queue | Notes |
|---|---|---|
| Registration/order confirmation emails | `emails` | Low latency matters — attendee is waiting |
| Payment receipt / invoice emails | `emails` | |
| CSV/Excel/PDF exports | `exports` | Can be slower; large-dataset streaming, not `Model::all()` |
| Ticket PDF / badge / certificate generation | `exports` | |
| Webhook-triggered order processing | `webhooks` | Isolated from user-facing email queue so a gateway retry storm doesn't delay confirmations |
| Expired reservation-hold release | `default` (scheduled) | Runs via scheduler, not a one-off dispatch |
| Post-event certificate trigger | `default` (scheduled) | |

Every job above must have `ShouldQueue` applied — Phase 11 explicitly
audits earlier phases for jobs accidentally left synchronous.

## Retry / Backoff

Default: 3 attempts, exponential backoff (`10s, 60s, 300s`). Payment
webhook processing uses a longer backoff ceiling since gateway-side
issues may take longer to resolve; exports use fewer retries since a
failed export should surface to the user quickly rather than silently
retry for minutes.

## Scheduler

Registered in Laravel's scheduler, with a documented cron entry for the
production crontab (Phase 11 — `DEPLOYMENT.md` has the actual crontab
line):
- Release expired reservation holds (Phase 5/6).
- Generate post-event certificates (Phase 7).

## Worker Management

Horizon (recommended) in Redis environments, with a documented supervisor
config as the fallback if Horizon isn't used. Horizon dashboard access is
gated behind the same auth/role checks as other internal tooling — never
publicly reachable.

## Failed Jobs

`failed_jobs` table (Phase 3 standard migration). Failed payment-webhook
jobs alert via the monitoring integration point (Phase 11); failed export
jobs surface a "your export failed, try again" state to the requesting
user rather than failing silently.
