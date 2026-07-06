# TENANCY.md

## Model

Shared-schema, single-database multi-tenancy. Every tenant-owned table has
`organization_id`. Isolation is automatic, not something every future
controller has to remember.

## Request Flow

```
auth:sanctum → resolve.tenant → tenant.required (when needed)
```

1. **`ResolveTenant`** runs on every authenticated API request. When an
   organization id is present in the route (`{organization}`) or the
   `X-Organization-Id` header, it validates membership and sets
   `TenantContext`. If an id is provided but the user is not a member,
   the request is rejected with **403**. Routes without an org id (e.g.
   `GET /me`, `GET /organizations`) proceed with no tenant context.
2. **`RequireTenantContext`** (`tenant.required` / `org.member`) runs on
   routes that require a resolved tenant. If `TenantContext` is empty,
   the request fails closed with **422** — never with unscoped data.

## Components

**`TenantContext` (service, singleton per request)**
Single source of truth for "what organization is this request acting
as." Set once in `ResolveTenant`, read everywhere else. Replaces the
Phase 1 `OrganizationContext` service.

**`ResolveTenant` (middleware, alias `resolve.tenant`)**
Runs early in the authenticated API stack. Sets `TenantContext` from the
authenticated user's route parameter or `X-Organization-Id` header.
403s if the user isn't a member of the requested org. When the org id
comes only from `X-Organization-Id` (not the route) and the user is not a
member, the header is ignored on routes that do not require tenant context
(e.g. invitation accept) so a stale switcher header cannot block unrelated
actions. On `tenant.required` / `org.member` routes, a non-member header
still returns **403**.

**`RequireTenantContext` (middleware, aliases `tenant.required`, `org.member`)**
Ensures `TenantContext` was resolved before the controller runs. Used on
org-scoped routes and any endpoint that queries tenant-owned models.

**`TenantScope` (global Eloquent scope)**
Resolves the active organization from `TenantContext` and automatically
filters every query by `organization_id`. Throws
`TenantContextUnresolvedException` (rendered as **422** on API routes)
if applied when no tenant context is resolvable — it fails closed, never
silently returns unscoped results.

**`BelongsToTenant` (trait)**
Applied to every tenant-owned model. Registers `TenantScope`,
auto-sets `organization_id` on create if not explicitly provided, and
exposes `withoutTenantScope()` as the one legitimate way to run a
cross-tenant query.

**`BelongsToTenantPolicy` (base policy)**
Model policies extend this to re-check that the authenticated user is a
member of the resource's organization **and** that the resource's
`organization_id` matches the resolved `TenantContext`. Defense in depth
alongside `TenantScope` — see `SECURITY.md`.

## Adding The Trait To A New Model

```php
use App\Traits\BelongsToTenant;

class NewModel extends Model
{
    use BelongsToTenant;
}
```

That's it for reads/writes. Also extend `BelongsToTenantPolicy` in the
model's Policy so authorization re-checks membership independent of the
scope (defense in depth — see `SECURITY.md`).

## The Escape Hatch

```php
NewModel::withoutTenantScope('reason for bypass')->get();
```

Used only for genuinely cross-tenant operations (e.g. a future
super-admin panel). Every use must be:
- Explicit and localized — not chained into a generic query builder
  helper that other code might call unknowingly.
- Logged (`security.log`) with the acting user and reason.
- Not reachable from any normal authenticated user-facing endpoint.

## Testing Isolation

Every tenant-owned model gets at least one test asserting:
- Org A's query never returns Org B's rows.
- Creating a row without an explicit `organization_id` gets the current
  tenant's id automatically.
- `withoutTenantScope()` is the only way to see cross-tenant data.
- Hitting an endpoint with no resolvable tenant context fails closed
  (403/422), never open.

Phase 2 uses `TenantFixture` (a throwaway test model) and
`GET/POST /api/v1/tenant-fixtures` until real business models exist in
Phase 3.

## Which Models Are Tenant-Scoped

Everything business-owned: `Event`, `EventSetting`, `Track`, `Session`,
`Speaker`, `Sponsor`, `Exhibitor`, `Booth`, `TicketType`, `Coupon`,
`Registration`, `Order`, `OrderItem`, `Invoice`, `Payment`, `Review`,
`Certificate`, `ActivityLog`, `Attachment`/`Media`, `SessionRegistration`,
`ExhibitorLead`, `ExhibitorContact` (portal auth).

## Exhibitor Portal Auth (Phase 8)

Exhibitor portal users are **not** organization members. They authenticate
via Sanctum as `ExhibitorContact` (`POST /api/v1/exhibitor-portal/auth/login`)
with tokens scoped to `exhibitor-portal`. Middleware `exhibitor.contact`
ensures the tokenable model is `ExhibitorContact`, not `User`.

- Each contact belongs to exactly one `Exhibitor` (and thus one event).
- Portal routes set tenant context from `contact->organization` before
  querying tenant-scoped models (`Registration`, `ExhibitorLead`, etc.).
- Exhibitors can only read/update their own exhibitor profile and leads;
  policies and service-layer checks enforce booth isolation.
- Frontend uses a separate auth store (`exhibitor-auth-store`) and API token
  — do not mix with organizer `User` tokens on `/exhibitor-portal/*` routes.

Not tenant-scoped: `User` (belongs to orgs via pivot, isn't itself owned by one),
`Organization` itself, Laravel's default `Notification`/`jobs`/
`failed_jobs` tables.

`TenantFixture` is a Phase 2 isolation fixture only — not a business model.
