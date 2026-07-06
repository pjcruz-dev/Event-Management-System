# FOLDER_STRUCTURE.md

Annotated directory layout for the Event Management SaaS monorepo. Backend
(Laravel) and frontend (Next.js) share the repository root.

## Repository Root

```
event-saas/
├── app/                    # Laravel application code (backend)
├── bootstrap/              # Laravel bootstrap and application factory
├── config/                 # Laravel configuration files
├── database/               # Migrations, factories, seeders
├── frontend/               # Next.js frontend (App Router lives in frontend/src/)
│   └── src/                # See "Frontend — frontend/src/" below
├── public/                 # Laravel web root (API entry via index.php)
├── routes/                 # Laravel route definitions (web, api, console)
├── storage/                # Laravel logs, cache, uploads (gitignored runtime)
├── tests/                  # PHPUnit/Pest backend tests
├── ai-development-kit/     # Extended reference documentation
├── prompts/                # Phase-by-phase Cursor build prompts
├── .cursor/rules/          # Cursor IDE rule files
├── FOLDER_STRUCTURE.md     # This file
├── NAMING_CONVENTIONS.md   # Cross-stack naming rules
├── DATABASE_NAMING_CONVENTION.md
├── API_STANDARDS.md        # API envelope stub (expanded in later phases)
└── QUEUE_STRATEGY.md       # Queue names and worker strategy
```

## Backend — `app/`

```
app/
├── Domain/                 # Business domains (Phase 1+); one subfolder per domain
│   ├── Auth/               # User auth flows
│   ├── Organization/       # Tenants, membership, invitations
│   ├── Event/              # Event CRUD, theme, landing pages
│   ├── Registration/       # Tickets, forms, coupons, holds
│   ├── Payment/            # Orders, gateways, refunds
│   ├── CheckIn/            # QR, badges, certificates
│   ├── Conference/         # Tracks, sessions, exhibitors
│   ├── Dashboard/          # Analytics and exports
│   └── Billing/            # Organizer subscriptions (Phase 13)
├── Services/               # Business logic services; extend BaseService
├── Actions/                # Single-purpose command objects; extend BaseAction
├── DTO/                    # Data transfer objects between layers
├── Repositories/           # Optional query abstractions (only when needed)
├── Policies/               # Authorization policies (Phase 1+)
├── Http/
│   ├── Controllers/Api/    # Thin API controllers
│   ├── Requests/           # Form Request validation classes
│   ├── Resources/          # API Resource transformers
│   └── Middleware/         # HTTP middleware (tenant resolution in Phase 2)
├── Traits/                 # Shared Eloquent traits (BelongsToTenant in Phase 2)
├── Helpers/                # Pure helper functions
├── Exceptions/             # Custom exceptions and API exception rendering
├── Events/                 # Laravel domain events
├── Listeners/              # Domain event listeners
├── Notifications/          # Notification classes; extend BaseNotification
├── Models/                 # Eloquent models (Phase 1+)
└── Support/
    └── Responses/          # ApiResponse envelope helper
```

## Frontend — `frontend/src/`

> Next.js lives in `frontend/` because Laravel already owns the root `app/`
> directory. Next.js prefers a root `app/` over `src/app/` when both exist.

```
frontend/
├── src/
│   ├── app/                # Next.js App Router pages and layouts
│   │   ├── (auth)/         # Login, register, password reset
│   │   ├── (dashboard)/    # Organizer dashboard
│   │   └── (public)/       # Public event pages
│   ├── components/
│   │   ├── ui/             # shadcn/ui primitives
│   │   ├── shared/         # Reusable product components
│   │   └── providers/      # Theme, Query providers
│   ├── features/           # Domain-specific UI (Phase 1+)
│   ├── lib/                # api-client, query-client, utils
│   ├── hooks/
│   ├── stores/
│   ├── types/
│   └── styles/
│       └── globals.css     # Tailwind + shadcn tokens (class dark mode)
├── next.config.ts
├── package.json
└── tsconfig.json
```

## What Goes Where — Quick Rules

| Artifact | Location |
|---|---|
| API endpoint | `routes/api.php` → `Http/Controllers/Api/` |
| Validation | `Http/Requests/` |
| Authorization | `Policies/` |
| Business logic | `Services/` or `Actions/` |
| Domain-specific UI | `frontend/src/features/<domain>/` |
| Reusable UI (no domain knowledge) | `frontend/src/components/shared/` |
| shadcn primitive | `frontend/src/components/ui/` |
