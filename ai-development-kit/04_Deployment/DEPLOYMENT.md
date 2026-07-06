# DEPLOYMENT.md

## Local Parity

```
docker-compose up -d
```

Brings up: Laravel app container, Next.js app container, MySQL 8, Redis —
matching production topology so "works locally" actually predicts "works
in prod."

## Target Deployment Approach

(State the actual choice once made — e.g. containers on a VPS via a
reverse proxy, or a managed platform. Placeholder structure below; fill
in the real target before Phase 11 is considered done.)

- **Backend** — Laravel app container, built from `Dockerfile.backend`,
  runs behind a reverse proxy (Nginx or the platform's built-in one)
  terminating TLS.
- **Frontend** — Next.js container, built from `Dockerfile.frontend`,
  served standalone (`next start`) behind the same proxy or on a
  separate edge-friendly host if using ISR heavily.
- **Workers** — separate long-running container(s) running
  `php artisan horizon` (or the supervisor-managed queue workers),
  scaled independently from the web container.
- **Scheduler** — a cron entry (or platform scheduler equivalent) hitting
  `php artisan schedule:run` every minute.

## Fresh Production Deploy — Steps

1. Provision MySQL 8 and Redis (managed services preferred over
   self-hosted where available).
2. Set every variable in `ENVIRONMENT.md`, particularly all secrets.
3. Build and push images (or deploy via the platform's build pipeline).
4. `php artisan migrate --force` (never `migrate:fresh` against
   production data).
5. Run the production seeder only if this is genuinely a first deploy
   with no real data — otherwise skip seeding entirely.
6. Start web, worker, and scheduler processes.
7. Verify: health check endpoint responds, a test webhook reaches the
   backend, queued email actually sends.

## CI/CD

GitHub Actions (the stack already uses GitHub): backend test suite,
frontend type-check + lint, a build check — all on every PR. A deploy
workflow triggers on merge to `main` (or a tag), running the steps above
against the target environment.

## Rollback

Container-based deploys roll back by redeploying the previous image tag.
Database migrations are written to be backward-compatible for at least
one release where feasible (additive columns before removing old ones in
a later release) so a rollback doesn't require an immediate down-migration
under pressure.
