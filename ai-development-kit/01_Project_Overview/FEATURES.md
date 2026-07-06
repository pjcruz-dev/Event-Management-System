# FEATURES.md

Feature inventory grouped by phase. "MVP" = required for a usable first
launch; "Later" = valuable but deferrable without blocking launch.

## Phase 1 — Auth & Organizations (MVP)
Register/login/logout, email verification, password reset, organizations
with owner/admin/member + custom roles, granular permissions, invitations,
profile + avatar upload, organization switcher.

## Phase 2 — Multi-Tenancy (MVP, invisible to users)
Automatic tenant scoping on every query, tenant-aware policies, fail-closed
on unresolvable tenant context.

## Phase 3 — Database Foundation (MVP, invisible to users)
Full schema: events, sessions/tracks, speakers, sponsors/exhibitors/booths,
ticket types, coupons, registrations, orders, invoices, reviews,
certificates, activity log.

## Phase 4 — Event Management (MVP)
Event CRUD, publish/archive/duplicate, slug + SEO fields, theme builder,
drag-reorderable landing page block builder. Custom domain *field* only —
verification is Later (see ROADMAP).

## Phase 5 — Registration & Ticketing (MVP)
Ticket types (VIP/Regular/Student/etc. as tag values), dynamic form
builder, coupons, oversell-safe reservation holds, waiting list, guest
checkout.

## Phase 6 — Payments (MVP)
Gateway-agnostic interface; Stripe + PayMongo (GCash) implementations;
webhook-verified order confirmation; refunds; invoices. Additional
gateways (PayPal/Maya/Xendit) are Later — the interface is designed so
adding them doesn't touch business logic.

## Phase 7 — QR Check-In (MVP)
Signed QR ticket per registration, PDF ticket + badge generation, scanner
UI with offline queue + sync, duplicate-scan prevention, certificate
generation.

## Phase 8 — Conference Module (Later, MVP if selling to conferences)
Tracks/sessions/speakers with conflict detection, sponsors/exhibitors/
booths, exhibitor portal, lead scanner.

## Phase 9 — Organizer Dashboard (MVP)
Revenue/registration/check-in analytics, CSV/Excel/PDF exports (streamed),
activity/audit feed.

## Phase 10 — Public Website (MVP)
Searchable event directory (`GET /discover/events` with SQL filters — category,
date range, location, pricing, keyword), SEO meta + JSON-LD, sitemap/robots,
public event pages rendering the organizer's theme/landing blocks (ISR),
public organizer profiles (opt-in), attendee reviews, "similar events"
heuristic (not ML — that's Phase 12).

**Search upgrade path:** Laravel Scout + Meilisearch/Algolia when event volume
or full-text relevance requirements outgrow indexed SQL `LIKE`/filter queries.
See `ai-development-kit/02_Backend/DISCOVER_SEARCH.md`.

## Phase 11 — Production Readiness (MVP before real traffic)
Redis-backed queues, scheduler, real email templates, S3 storage
confirmed, monitoring hooks, rate limiting, security headers, upload
validation audit, feature flags + subscription-plan mechanism, CI, Docker,
deployment/environment/backup docs.

## Later (not in any phase 0–11 prompt)
AI Event Assistant, AI content generators, ML recommendation engine,
webhooks, public API, plugin system, super-admin panel, billing charges to
organizers, DNS-verified custom domains, i18n/multi-currency, native/PWA
app, product marketing site, legal/compliance docs, load testing,
full observability stack. See `ROADMAP.md` for sequencing.

## Phase 22 — RSVP & Guest Management ✅
Invite-first guest list, personal RSVP links (accept/decline/maybe),
plus-ones, CSV import, invitation/reminder emails, seating tables with
list-based assignment (table on tickets and check-in) — extends Phase 5/7
without replacing open conference registration. See
`ai-development-kit/02_Backend/RSVP_GUEST_MANAGEMENT.md`.
