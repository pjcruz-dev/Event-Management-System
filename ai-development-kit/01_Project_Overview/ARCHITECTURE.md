# ARCHITECTURE.md

## What This Is

A production-grade, multi-tenant Event Management SaaS platform. Organizations
(tenants) run events; attendees register, pay, get checked in via QR; organizers
get analytics and reporting. Not a demo — every module assumes real traffic,
real money, and real data-isolation requirements.

## Tech Stack

**Frontend** — Next.js 15 (App Router), TypeScript (strict), Tailwind CSS,
shadcn/ui, TanStack Query, React Hook Form + Zod, Zustand.

**Backend** — Laravel 11, PHP 8.3, MySQL 8, Laravel Sanctum, Spatie Permission,
Laravel Queues/Events/Notifications/Policies, Redis (queues + cache in
non-local environments).

## High-Level Shape

```
Browser (Next.js, SSR/ISR for public pages, CSR for dashboard)
      |
      v
Laravel API (/api/v1/...)  --  standard JSON envelope
      |
      +-- Sanctum auth (bearer tokens, multi-client: web + future mobile)
      +-- ResolveTenant middleware -> TenantContext -> TenantScope
      +-- Policies (authorization) -> Form Requests (validation)
      +-- Thin Controllers -> Services/Actions (business logic) -> Models
      |
      v
MySQL 8 (shared schema, organization_id on every tenant table)
Redis (queues, cache, tenant-prefixed keys)
S3-compatible storage (avatars, logos, tickets, badges, certificates)
```

## Multi-Tenancy Model

Shared-schema, single-database. Every tenant-owned table carries
`organization_id`. Isolation is enforced by a global Eloquent scope
(`BelongsToTenant` trait + `TenantScope`), not by convention or by
remembering to add a `WHERE` clause in every controller. See `TENANCY.md`.

## Domain-Driven Layout

Backend code is grouped by business domain under `app/Domain/<Domain>`, not
by technical layer. Cross-cutting concerns (auth, tenancy, responses,
logging, caching, storage) live in `Services/`, `Support/`, `Traits/`.
Controllers stay thin — orchestration only; business logic lives in
Services/Actions.

## Request Lifecycle (typical mutating request)

1. Sanctum authenticates the bearer token.
2. `ResolveTenant` middleware sets `TenantContext` from the user's active
   org (or `X-Organization-Id` header), 403s if not a member.
3. Route → Controller (thin) → Form Request (validation) → Policy
   (authorization, tenant-aware) → Service/Action (business logic).
4. Eloquent writes are automatically tenant-scoped via `BelongsToTenant`.
5. Response is wrapped in the standard envelope via `ApiResponse` — see
   `API_STANDARDS.md`.

## Build Order

The system is built in 12 sequential phases (0–11 shipped, 12 planned).
Each phase depends only on prior phases — see `ROADMAP.md` for the full
dependency table and current status. Do not build ahead of the current
phase; do not silently invent a dependency that hasn't been built yet.

## Documents In This Kit

This file is the map. For specifics, go to:
- Data model & indexing → `DATABASE_GUIDELINES.md`
- API shape/versioning → `API_STANDARDS.md`
- Auth/roles/invitations → `AUTH.md`
- Tenant isolation → `TENANCY.md`
- Payments → `PAYMENTS.md`
- Frontend conventions → `03_Frontend/*`
- Deploying it → `04_Deployment/*`
