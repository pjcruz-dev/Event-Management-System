# PHASE 2 — Multi-Tenancy Enforcement

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 0, Phase 1 (Organization model must exist).

## Objective

Make tenant isolation automatic and impossible to forget, rather than
something every future controller has to remember to do manually. This
phase produces the trait/scope/middleware trio that every tenant-owned
model from Phase 3 onward will use.

## Backend Tasks

### `BelongsToTenant` Trait
- Adds a global `TenantScope` that automatically filters queries by
  `organization_id` based on the currently resolved tenant context.
- Automatically sets `organization_id` on create if not explicitly
  provided.
- Provides a `withoutTenantScope()` static helper for the rare legitimate
  cross-tenant query (e.g., a super-admin panel), used explicitly and
  logged.

### `TenantScope` (Global Scope class)
- Resolves the active organization from a `TenantContext` singleton
  (bound per-request from the authenticated user's active organization,
  or from a route/header parameter for endpoints that specify it
  explicitly).
- Throws a clear exception if applied to a model when no tenant context
  is resolvable, rather than silently returning unscoped results.

### `TenantContext` Service
- Single source of truth for "what organization is this request acting
  as." Set once per request (typically in middleware), read everywhere
  else.

### Middleware
- `ResolveTenant` — runs early in the API middleware stack, sets
  `TenantContext` from the authenticated user's active org (or a
  `X-Organization-Id` header if the user belongs to multiple orgs),
  and 403s if the user isn't a member of the requested org.

### Policies
- A `BelongsToTenantPolicy` base trait/class other model policies can use
  so authorization always double-checks tenant membership in addition to
  role — defense in depth, not reliance on the scope alone.

## Explicitly Deferred to Phase 3

The trait must be written now, but it will not be **applied** to
business models yet, since those models (Events, Tickets, Orders, etc.)
don't exist until Phase 3. Phase 3's prompt will say "apply
`BelongsToTenant` to X, Y, Z" — do not create those models here.

## Tests

- A test that creates two organizations with a model that uses
  `BelongsToTenant` (use a throwaway test model/fixture if no real
  tenant-owned model exists yet), and asserts:
  - Org A's query never returns Org B's rows.
  - Creating a row without an explicit `organization_id` gets the current
    tenant's id automatically.
  - `withoutTenantScope()` is the only way to see cross-tenant data, and
    is not reachable from a normal authenticated request.
- A test that hits an API endpoint without a resolvable tenant and
  confirms it fails closed (403/422), never open.

## Definition of Done

- `TENANCY.md` documents how the scope resolves the tenant, how to add
  the trait to a new model, and the explicit escape hatch for legitimate
  cross-tenant operations.
- List every file created or modified.
- Confirm: no business models were created in this phase.
