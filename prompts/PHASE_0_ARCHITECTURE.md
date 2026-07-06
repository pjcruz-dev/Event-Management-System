# PHASE 0 — Architecture Foundation

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.

## Objective

Establish the complete skeletal architecture for both the Laravel backend
and the Next.js frontend, **before any business feature exists**. Every
later phase will build inside the structure created here, so it must be
unambiguous and fully documented.

Do not build Events, Users, Tickets, or any other business feature in this
phase. This phase is structure only.

## Backend Tasks (Laravel)

Create the following directory structure under `app/`:

```
app/
├── Domain/
│   ├── <PascalCaseDomain>/         # one folder per business domain, created empty except a README stub explaining its purpose — populated in later phases
├── Services/
├── Actions/
├── DTO/
├── Repositories/
├── Policies/
├── Http/
│   ├── Controllers/Api/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
├── Traits/
├── Helpers/
├── Exceptions/
├── Events/
├── Listeners/
├── Notifications/
└── Support/
    └── Responses/          # ApiResponse helper class
```

Deliverables:

1. **`ApiResponse` helper** (`app/Support/Responses/ApiResponse.php`) —
   static methods `success()`, `error()`, `paginated()` that return the
   standard JSON envelope from the master prompt.
2. **Base `Service` and base `Action` abstract classes** with shared
   conventions (constructor property promotion, single `execute()` /
   `handle()` entrypoint convention).
3. **Global exception handler strategy** — a custom `Handler.php` (or
   Laravel 11 `bootstrap/app.php` exception config) that converts
   validation errors, model-not-found, authorization failures, and
   unhandled exceptions into the standard JSON envelope, with correct HTTP
   status codes.
4. **Queue strategy doc + config** — define which queue connection/driver
   is used per job type (default, emails, exports, webhooks), and stub the
   corresponding `config/queue.php` connections.
5. **Notification strategy** — base `Notification` class conventions
   (channels array pattern, database notification enabled by default).
6. **Storage strategy** — configure `config/filesystems.php` disks for
   `local`, `public`, and `s3` (or S3-compatible), with a `StorageService`
   wrapper that later phases use instead of calling `Storage::` directly.
7. **Cache strategy** — a `CacheService` wrapper with tenant-aware key
   prefixing (`org:{organization_id}:...`) so later caching never leaks
   across tenants.
8. **Logging strategy** — dedicated log channels: `security.log`,
   `payments.log`, `audit.log`, separate from the default Laravel log.

## Frontend Tasks (Next.js)

Create the following structure under `src/`:

```
src/
├── app/                     # App Router route groups, empty shells only
│   ├── (auth)/
│   ├── (dashboard)/
│   └── (public)/
├── components/
│   ├── ui/                  # shadcn/ui primitives live here
│   └── shared/
├── features/                # one folder per domain feature, populated later
├── lib/
│   ├── api-client.ts        # typed fetch wrapper around the backend API envelope
│   ├── query-client.ts      # TanStack Query provider setup
│   └── utils.ts
├── hooks/
├── stores/                  # Zustand stores
├── types/
└── styles/
```

Deliverables:

1. `lib/api-client.ts` — a typed wrapper that assumes the backend's
   standard JSON envelope, handles auth token attachment, and normalizes
   errors into a single `ApiError` type.
2. `lib/query-client.ts` — TanStack Query provider with sane defaults
   (staleTime, retry policy) wired into the root layout.
3. Tailwind config with dark mode via class strategy, and the shadcn/ui
   theme tokens installed (no custom color decisions yet — just wiring).
4. A `types/api.ts` with the shared envelope types (`ApiResponse<T>`,
   `PaginatedResponse<T>`, `ApiError`).

## Documentation To Produce

Write these as real files, not just code comments:

- `FOLDER_STRUCTURE.md` — the tree above, annotated with one sentence per
  folder explaining what belongs there.
- `NAMING_CONVENTIONS.md` — casing rules for PHP classes, TS
  types/interfaces, DB tables/columns, API routes, and React components.
- `DATABASE_NAMING_CONVENTION.md` — snake_case tables (plural), singular
  model names, foreign key pattern (`{singular}_id`), pivot table naming.
- `API_STANDARDS.md` (stub, expanded later) — the JSON envelope shape,
  pagination shape, error code conventions, versioning approach
  (`/api/v1/...`).

## Explicitly Out of Scope for This Phase

- No User, Organization, Event, or any other business model.
- No auth logic (that's Phase 1).
- No actual UI screens beyond the empty route-group shells.

## Definition of Done

- `php artisan serve` boots with no errors on an empty schema.
- `npm run dev` boots with no errors and dark mode toggles correctly on a
  placeholder page.
- All four documentation files exist and are internally consistent with
  each other (e.g., naming conventions match what's actually in the code).
- List every file created, and confirm no business logic was introduced.
