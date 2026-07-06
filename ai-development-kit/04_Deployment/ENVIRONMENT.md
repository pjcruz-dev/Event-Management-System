# ENVIRONMENT.md

Every required env var across both apps. Fill in real values only in
your actual `.env` files — never commit secrets to the repo.

## Backend (Laravel)

| Variable | Description | Secret |
|---|---|---|
| `APP_ENV` | `local` / `staging` / `production` | no |
| `APP_KEY` | Laravel app encryption key | yes |
| `APP_URL` | Public backend URL | no |
| `APP_DEBUG` | Must be `false` in production | no |
| `DB_CONNECTION` / `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | MySQL connection | password: yes |
| `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD` | Redis connection | password: yes |
| `QUEUE_CONNECTION` | `sync` local, `redis` elsewhere | no |
| `SANCTUM_STATEFUL_DOMAINS` | Frontend domain(s) for Sanctum | no |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | Transactional email provider | password: yes |
| `FILESYSTEM_DISK` | `local` / `s3` | no |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_BUCKET` / `AWS_DEFAULT_REGION` | S3 (or compatible) storage | key/secret: yes |
| `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` | Stripe gateway | yes |
| `PAYMONGO_PUBLIC_KEY` / `PAYMONGO_SECRET_KEY` / `PAYMONGO_WEBHOOK_SECRET` | PayMongo gateway | yes |
| `SENTRY_DSN` (or equivalent) | Error tracking integration point | yes |

## Frontend (Next.js)

| Variable | Description | Secret |
|---|---|---|
| `NEXT_PUBLIC_API_URL` | Backend API base URL | no |
| `NEXT_PUBLIC_APP_URL` | Frontend's own public URL (used in metadata, sitemap) | no |
| `NEXT_PUBLIC_SENTRY_DSN` (or equivalent) | Frontend error tracking | yes* (client-exposed but treat as sensitive-adjacent) |

## Rules

- Anything marked "secret" never appears in a client bundle, a log line,
  or an API response body (see `SECURITY.md`).
- `NEXT_PUBLIC_*` variables are, by Next.js design, shipped to the
  browser — never put a real secret behind that prefix.
- Keep this table in sync whenever a phase introduces a new integration
  (new gateway, new storage provider, etc.) — an undocumented required
  env var is a deploy-day surprise.
