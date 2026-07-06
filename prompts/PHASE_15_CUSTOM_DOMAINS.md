# PHASE 15 — Custom Domain Verification

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 4 (`custom_domain` field + status enum already exist
> as schema-only stubs — this phase makes them actually work), Phase 11
> (feature-flag mechanism, since custom domains are typically plan-gated).

## Objective

Turn the Phase 4 `custom_domain` stub into a working feature: an
organizer can point their own domain at their event's public page, with
real DNS verification and automatic SSL.

## Backend Tasks

### DNS Verification Flow
- `POST /events/{event}/custom-domain` — organizer submits a domain,
  status set to `pending`, backend generates a unique verification token
  and returns the exact DNS record (TXT record recommended over CNAME-only
  verification, since it doesn't require the domain to already point at
  you) the organizer must add at their registrar.
- `POST /events/{event}/custom-domain/verify` — checks the DNS record via
  a real DNS lookup (not a cached/assumed result), transitions status to
  `verified` on success, returns a clear failure reason (record not
  found / wrong value / DNS not propagated yet) on failure rather than a
  generic error.
- A scheduled job re-checks `pending` domains periodically (DNS
  propagation can take hours) and auto-transitions to `verified` without
  the organizer needing to manually re-click — surfaces a notification
  when it succeeds.

### SSL Provisioning
- Once `verified`, provision SSL for the custom domain. Two viable
  approaches — pick one and document the choice in `DEPLOYMENT.md`:
  (a) a CDN/edge provider that handles SaaS custom domains + auto-SSL
  (e.g. Cloudflare for SaaS, Vercel domains API), or (b) self-managed via
  Let's Encrypt + a reverse-proxy config reload. Prefer (a) unless there's
  a specific reason to self-manage — it removes an entire class of
  renewal/expiry failure modes.
- Domain status gains a `ssl_pending` / `active` distinction if using
  approach (b), since SSL issuance isn't instant.

### Routing
- Reverse proxy / edge config routes an incoming request on the custom
  domain to the correct organization's event, based on a domain →
  event/org lookup table kept in sync with `verified` domains — this
  lookup must be fast (cached, not a DB query per request) since it sits
  on the request hot path for every custom-domain visitor.
- A verified custom domain serves *that event's* public page at the root
  path — the platform's own routing (`/events/[slug]`) still works in
  parallel; the custom domain is an additional entry point, not a
  replacement.

## Frontend Tasks

- `(dashboard)/events/[id]/settings/domain` — domain entry form, the DNS
  record to add (copyable), a "verify now" button, live status
  (pending/verified/failed) with the specific failure reason shown.
- Plan-gating: if custom domains are plan-restricted (Phase 11/13), a
  clear upgrade prompt instead of a raw 403.

## Security

- Verify domain ownership via DNS TXT record specifically because it
  proves control of the domain without requiring it to already be live —
  never accept a self-reported "I own this domain" without the DNS
  challenge.
- Rate-limit the verify endpoint (DNS lookups are cheap to abuse if
  unthrottled).
- A domain that fails verification repeatedly or is removed by the
  organizer is fully de-provisioned (SSL cert revoked/not renewed,
  routing entry removed) — no orphaned routing rules left pointing at a
  domain the organizer no longer controls.

## Tests

- Valid TXT record → verification succeeds; missing/wrong record →
  verification fails with a specific, correct reason.
- A verified domain routes to the correct event; an unverified or
  never-configured domain does not route at all (no fallback that leaks
  which org "almost" owns it).
- Removing a custom domain fully removes its routing entry — confirmed by
  a follow-up request to the old domain returning a clean 404, not a
  stale cached route.

## Definition of Done

- An organizer can add a custom domain, follow the DNS instructions
  against a real test domain, verify it, and have the event's public page
  load correctly over HTTPS at that domain.
- `DEPLOYMENT.md` updated with the chosen SSL/routing approach.
- List every file created or modified.
