# PHASE 14 — Super-Admin Panel

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 2 (multi-tenancy, including the `withoutTenantScope()`
> escape hatch), Phase 3 (all models exist), Phase 13 if billing support
> tooling is wanted (optional — flag if skipped).

## Objective

Build the internal, cross-tenant operator panel: a way for your own team
to view all organizations, investigate support issues, impersonate a user
for debugging, and suspend a tenant — using the tenancy escape hatch that
was deliberately built in Phase 2 and left unused until now.

## Backend Tasks

### Super-Admin Guard
- A distinct auth guard/role (`is_super_admin` on `User`, or a separate
  `SuperAdmin` model — state which and why in `TENANCY.md`'s follow-up
  section) — never reuses an organization's `owner` role as a stand-in
  for platform-level access.
- Every super-admin action is logged to `security.log` with actor, action,
  and target organization/user — no exceptions, including read-only
  views of sensitive data (e.g. viewing a `Payment.raw_payload`).

### Cross-Tenant Endpoints
- `GET /admin/organizations` — paginated, searchable list across all
  tenants, using `withoutTenantScope()` explicitly and only here.
- `GET /admin/organizations/{org}` — detail view: plan, usage, recent
  activity, support flags.
- `POST /admin/organizations/{org}/suspend` / `/reactivate` — sets an
  `is_suspended` flag checked by `ResolveTenant` middleware (Phase 2),
  blocking all API access for that org's users with a clear error, not a
  generic 500.
- `POST /admin/impersonate/{user}` — issues a short-lived, clearly-scoped
  Sanctum token that lets a super-admin act as that user for support
  purposes. Every request made under an impersonation token is tagged in
  `ActivityLog` as "performed via impersonation by {admin}" — never
  indistinguishable from the real user's own action.
- `POST /admin/impersonate/stop` — ends the impersonation session
  explicitly; sessions also auto-expire on a short TTL regardless.

### Platform Health
- `GET /admin/metrics` — platform-wide aggregate (not per-org) counts:
  total orgs, active subscriptions (if Phase 13 exists), queue depth,
  failed job count, recent signups — read-only, aggregate-only, never
  exposes one org's data through a "platform" lens.

## Frontend Tasks

- Separate route group, e.g. `(admin)/...`, with its own layout, visually
  distinct from the organizer dashboard so nobody confuses which context
  they're in.
- `(admin)/organizations` — searchable table, suspend/reactivate action
  with confirm dialog stating the consequence.
- `(admin)/organizations/[id]` — detail view, impersonate button with a
  loud, unmissable "you are now acting as {user}" banner shown for the
  entire duration of an impersonation session on every subsequent screen.
- `(admin)/metrics` — platform health dashboard.

## Authorization

- `SuperAdminPolicy` — every admin endpoint checks this first, before any
  other logic runs. Regular organization Policies are never modified to
  accommodate super-admin access; the admin surface is additive and
  separate, not a bypass wired into existing policies.
- Impersonation tokens carry a distinct ability/scope
  (`Sanctum::actingAs` with an `impersonated` ability marker) so any
  future code can check "is this request happening under impersonation"
  if it needs to behave differently (e.g. never allow a destructive
  billing action while impersonating).

## Tests

- A non-super-admin user cannot reach any `/admin/*` endpoint, even with
  a valid token for an org they own.
- Suspending an organization immediately blocks all of that org's users
  from every non-admin endpoint, verified via a real request, not just a
  flag check.
- Every impersonation action is distinguishably logged as impersonated,
  not indistinguishable from the real user acting normally.
- `withoutTenantScope()` usage in this phase is confirmed to be the only
  new usage introduced — no other phase's code silently gained
  cross-tenant access as a side effect.

## Definition of Done

- A super-admin can log in, search organizations, suspend one (confirming
  that org's users are locked out), reactivate it, and impersonate a user
  to view their dashboard — full round trip, fully audit-logged.
- List every file created or modified.
