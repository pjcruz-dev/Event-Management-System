# SECURITY.md

## Authentication

Laravel Sanctum, bearer tokens (not SPA cookie mode) — chosen because the
API serves multiple clients (web now, mobile later). Tokens are scoped
per-device/session and revocable individually from the profile settings
screen.

## Tenant Isolation (Defense In Depth)

Isolation is enforced twice, deliberately redundant:
1. `TenantScope` (global Eloquent scope) — the primary mechanism.
2. Policies extend `BelongsToTenantPolicy`, which re-checks tenant
   membership independent of the scope — a policy never trusts "the
   query already filtered this" as its only check.

See `TENANCY.md` for the mechanics. The `withoutTenantScope()` escape
hatch is used only in explicitly logged, reviewed code paths (e.g. a
future super-admin panel) — never reachable from a normal authenticated
request.

## Rate Limiting

Distinct limits per endpoint class, not one global limit:
- Auth endpoints (login, register, password reset): tight, IP + email
  keyed.
- Public event registration: moderate, tuned to survive a legitimate
  ticket-drop spike without allowing scripted oversell attempts.
- Webhook endpoints (payment gateways): keyed by source, generous enough
  for gateway retries, but every request is signature-verified before
  any processing regardless of rate limit status.

## Webhooks

Every webhook handler verifies the provider's signature **before** doing
anything else. Unsigned or invalid-signature requests are logged
(`payments.log`) and rejected — never processed "just to see."

## File Uploads

Every upload path (avatar, org logo, event theme assets, exhibitor
materials, attachments) validates MIME type and size server-side, not
just via the `accept` attribute on the frontend input. Phase 11 audits
every upload path introduced in earlier phases rather than assuming they
were done correctly the first time.

## Secrets

API keys and gateway credentials are read from env/config only — never
hardcoded, never returned in any API response body, never logged in
plaintext (payment payload snapshots in `Payment.raw_payload` are stored
for audit, but redact card-adjacent fields before persisting if a
provider ever includes them, which hosted-checkout flows should avoid by
design).

## Security Headers

CSP, HSTS, X-Frame-Options, X-Content-Type-Options configured at the
Next.js and/or reverse-proxy level (Phase 11), tuned per environment
(looser CSP locally for dev tooling, strict in production).

## Logging

Dedicated channels, separate from the default app log: `security.log`
(auth events, permission denials), `payments.log` (every webhook +
refund action, success or failure), `audit.log` (`ActivityLog` entries).
In production these write to a destination that survives container
restarts (stdout/stderr for log collection, or a documented external
sink) — never local files that vanish on redeploy.

## Never Store Raw Card Data

Stripe/PayMongo hosted checkout means the platform never touches card
numbers. Confirm this holds at implementation time for every gateway
added — it's a design constraint, not just a starting assumption.

## Reporting a Vulnerability

(Fill in once a real disclosure channel exists — e.g. a security@ email
or a private reporting form. Do not leave this section silently blank in
a public repo; either fill it or explicitly mark it as internal-only.)
