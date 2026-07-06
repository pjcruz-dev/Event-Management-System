# REDIS.md

## Usage

Redis serves three distinct purposes in this stack — kept logically
separate even if sharing one instance in smaller environments:

1. **Queues** — job dispatch/processing (see `QUEUES.md`), managed via
   Horizon in non-local environments.
2. **Cache** — `CacheService` (Phase 0), tenant-prefixed keys
   (`org:{organization_id}:...`) so cached data never leaks across
   tenants, plus dashboard metric caching (Phase 9).
3. **Sessions** (if session-based auth is ever used alongside Sanctum
   tokens — otherwise not needed, since bearer tokens don't require
   server-side session storage).

## Configuration Per Environment

| Environment | Setup |
|---|---|
| local | Single Redis container via `docker-compose`, no persistence needed |
| staging | Managed Redis instance or a dedicated container, AOF persistence recommended |
| production | Managed Redis (preferred) with persistence + backups, or a dedicated instance sized for queue + cache load together |

## Key Namespacing

- Cache: `org:{organization_id}:{resource}:{identifier}` — never a bare
  key that could collide across tenants or across cache vs. queue usage.
- Queue: Laravel/Horizon's default naming, separated by queue name
  (`emails`, `exports`, `webhooks`, `default` — see `QUEUES.md`).

## Sizing Guidance

Queue and cache workloads are different in shape (queues: bursty,
write-heavy; cache: read-heavy, TTL-bounded) — if traffic grows enough
that they compete for memory/eviction priority, split into two Redis
instances/databases rather than tuning one shared instance around both.

## Failure Behavior

Cache being unavailable should degrade gracefully (fall through to the
DB query, don't 500 the request) — `CacheService` wraps reads in a
try/catch that logs and falls through rather than propagating a Redis
connection error to the user. Queue being unavailable is more serious
(jobs won't dispatch) and should alert via the monitoring integration
point (Phase 11), not fail silently.
