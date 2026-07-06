# QUEUE_STRATEGY.md

Queue configuration for the Event Management SaaS platform. Canonical job-type
mapping also lives in `ai-development-kit/02_Backend/QUEUES.md`; this file
tracks the **runtime config** wired in Phase 0.

## Connections

| Environment | `QUEUE_CONNECTION` | Notes |
|---|---|---|
| local | `sync` or `database` | Fast feedback; no Redis required for solo dev |
| staging / production | `redis` | Managed via Laravel Horizon (Phase 11) |

## Named Queues

Configured in `config/queue.php` under the `names` key. Job classes set
`$this->queue` to one of these values:

| Queue name | Job types | Priority |
|---|---|---|
| `default` | Scheduled maintenance (hold expiry, certificate triggers) | Normal |
| `emails` | Registration confirmations, receipts, invitations | High |
| `exports` | CSV/Excel/PDF exports, ticket/badge/certificate PDFs | Low |
| `webhooks` | Payment gateway webhook processing | Isolated |

## Retry / Backoff

- Default: 3 attempts, exponential backoff (`10s`, `60s`, `300s`).
- Webhook jobs: longer backoff ceiling (gateway-side retries).
- Export jobs: fewer retries — surface failure to the user quickly.

## Failed Jobs

Stored in the `failed_jobs` table (standard Laravel migration). Failed
payment-webhook jobs alert via monitoring (Phase 11); failed export jobs
show a retry affordance in the dashboard UI (Phase 9).

## Worker Commands (reference)

```bash
# Local — all queues on one worker
php artisan queue:work --queue=emails,webhooks,exports,default

# Production — Horizon manages workers per queue (Phase 11)
php artisan horizon
```

## Env Vars

| Variable | Default | Purpose |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | Driver |
| `QUEUE_NAME_DEFAULT` | `default` | Default queue name |
| `QUEUE_NAME_EMAILS` | `emails` | Email queue |
| `QUEUE_NAME_EXPORTS` | `exports` | Export queue |
| `QUEUE_NAME_WEBHOOKS` | `webhooks` | Webhook queue |
