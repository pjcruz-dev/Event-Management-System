# CODING_STANDARDS.md

## Non-Negotiables (apply everywhere)

1. No placeholder code, stub methods, or `// TODO` comments.
2. No skipped validation on any input boundary.
3. No hardcoded values that belong in config/env/DB.
4. Every Eloquent model defines its relationships explicitly.
5. Every migration defines indexes and foreign keys with an explicit
   `onDelete` behavior.
6. Every API response uses the standard envelope (`API_STANDARDS.md`).
7. All UI supports dark mode, is responsive, meets WCAG AA basics.

## PHP / Laravel

- PHP 8.3, strict types (`declare(strict_types=1)`) in all new files.
- PSR-12 via Laravel Pint — run `./vendor/bin/pint` before commit.
- Thin controllers: a controller method orchestrates (validate → policy →
  call a Service/Action → return a Resource); it never contains business
  logic directly.
- Services: one focused responsibility, constructor property promotion,
  `execute()` entrypoint convention (see base `Service`/`Action` classes
  from Phase 0).
- Form Requests for every mutating endpoint — never inline
  `$request->validate()`.
- Policies for every authorization decision — never inline
  `if ($user->role === 'admin')` checks in controllers.
- API Resources for every response — never `return $model` directly.
- Naming: `PascalCase` classes, `camelCase` methods/variables,
  `snake_case` DB columns, `kebab-case` API route segments.

## TypeScript / React / Next.js

- TypeScript strict mode, no `any` without an explicit inline reason.
- ESLint + Prettier — run `npm run lint` before commit.
- Server Components by default; `"use client"` only where interactivity
  requires it.
- Data fetching via TanStack Query; no ad-hoc `useEffect` fetch loops.
- Forms: React Hook Form + Zod schema, validated client- and server-side
  (server-side is authoritative — never trust the client alone).
- Naming: `PascalCase` components, `camelCase` hooks/functions prefixed
  `use`, `kebab-case` file names for routes, `PascalCase.tsx` for
  components.

## Database

See `DATABASE_GUIDELINES.md` for full naming/indexing rules.

## Testing

- Every phase that defines a "Tests" section in its prompt must have
  those tests passing before the phase is considered done — not
  optional, not "left for later."
- Backend: PHPUnit/Pest feature tests hit the actual HTTP layer, not
  just service methods in isolation, for anything auth/tenancy-sensitive.
- Concurrency-sensitive logic (oversell prevention, duplicate check-in)
  must have a test that actually fires parallel/racing requests, not
  just a sequential happy-path test.

## Security Baseline

See `SECURITY.md`. The short version: validate everything server-side,
verify every webhook signature, never log secrets, never return API keys
in a response body.
