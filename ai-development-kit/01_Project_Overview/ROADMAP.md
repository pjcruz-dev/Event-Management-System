# ROADMAP.md

## Status Legend
- ⬜ Not started  ·  🟨 In progress / partial  ·  ✅ Done  ·  🔒 Blocked on dependency

## Core Build (Phases 0–11)

| Phase | Name | Depends on | Status |
|---|---|---|---|
| 0 | Architecture Foundation | — | ✅ |
| 1 | Auth & Organizations | 0 | ✅ |
| 2 | Multi-Tenancy Enforcement | 0, 1 | ✅ |
| 3 | Database Foundation | 0–2 | ✅ |
| 4 | Event Management | 0–3 | ✅ |
| 5 | Registration & Ticketing | 0–4 | ✅ |
| 6 | Payments | 0–5 | 🟨 Partial |
| 7 | QR Check-In | 0–6 | ✅ |
| 8 | Conference Module | 0–7 | ✅ |
| 9 | Organizer Dashboard | 0–8 | ✅ |
| 10 | Public Website | 0, 4 (generally 0–9) | ✅ |
| 11 | Production Readiness | all prior | ⬜ |

Rule: do not start a phase until every phase in its "Depends on" column is
✅. If a phase surfaces a missing dependency mid-build, stop and flag it —
do not invent the missing piece inline.

**Phase 6 note:** Stripe Checkout, webhooks, public checkout URLs, and
refund processing were added ad-hoc (outside the formal Phase 6 prompt).
PayMongo/GCash, PayPal, Maya, and Xendit gateways remain pending. Free
orders still auto-fulfill to `paid`; organizers can also
`POST /events/{event}/orders/{order}/mark-paid` for manual/cash confirmation.

## Extended Phases (12–22) — Prompts Written, Not Yet Built

All prompts below exist in `prompts/` (same directory as Phases 0–11),
written in the same master-prompt + phase-prompt format. Paste
`prompts/00_MASTER_PROMPT.md` above each, same as any core phase. Build
order matters less between these than within 0–11, but respect each file's
own "Depends on" line.

| Phase | Name | Depends on | Status |
|---|---|---|---|
| 12 | AI & Enterprise Features | 0–11 | ⬜ prompt ready |
| 13 | Organizer Billing & Subscriptions | 6, 11 | ⬜ prompt ready |
| 14 | Super-Admin Panel | 2, 3, (13 optional) | ⬜ prompt ready |
| 15 | Custom Domain Verification | 4, 11 | ⬜ prompt ready |
| 16 | Internationalization & Multi-Currency | 4, 5, 9 | 🟨 Partial |
| 17 | Product Marketing Site | 1, (13 optional) | ⬜ prompt ready |
| 18 | Legal & Compliance | 1, 6, 13 | ⬜ prompt ready |
| 19 | Load & Performance Testing | 11 | ⬜ prompt ready |
| 20 | Full Observability | 11 | ⬜ prompt ready |
| 21 | Installable PWA & Mobile Readiness | 7, 10 | ⬜ prompt ready |
| 22 | RSVP & Guest Management | 5, 7, 10 (11 recommended) | ✅ |

Notes:
- Phase 16 partial: organization default currency setting and correct
  display formatting are done; full i18n and FX conversion are not.
- Phase 18 is document-drafting-heavy; its Definition of Done explicitly
  does not include "legally sufficient" — real attorney review remains a
  required manual step regardless of how complete the technical build is.
- Phase 19 is a periodic/pre-launch exercise, not a one-time build — rerun
  it after any major change to checkout, check-in, or discovery.
- Phase 22 adds invite-first RSVP, guest list, and seating for galas and
  weddings; it extends (does not replace) Phase 5 registration and Phase
  7 check-in.
- None of 12–22 are prerequisites for launching the core product (0–11).
  Sequence them based on actual business need — e.g. skip straight to 13
  (billing) if monetizing organizers is the priority, or 18 (legal) if
  launch is imminent.

## Event Page Builder Hardening (Phases 23–27)

Addresses gaps in the Phase 4 theme/landing builder (preview drift,
unused layout variants, thin asset UX, draft preview, block settings,
templates). Prompts in `prompts/PHASE_23_*` through `PHASE_27_*`. Run in
order after Phases 4, 8, and 10 are ✅.

| Phase | Name | Depends on | Status |
|---|---|---|---|
| 23 | Preview Fidelity & Layout Variants | 4, 8, 10 | ✅ |
| 24 | Theme Assets & Typography | 23 | ✅ |
| 25 | Draft Preview & Builder UX | 23, 24 | ✅ |
| 26 | Block Enhancements | 23–25, 8 | ✅ |
| 27 | Templates & Accessibility | 23–26 | ⬜ prompt ready |

**Recommended next formal phase:** Phase 27 (event-type templates, theme
presets, contrast checks). Phase 11 (production readiness) can run in
parallel if launch is the gate.

## Ad-Hoc Feature Work (not formal phases)

Completed outside the phase prompt sequence, driven by RSVPify gap analysis
and "complete existing flows first" prioritization.

| Feature | Status | Notes |
|---------|--------|-------|
| Refund management | ✅ | Orders list page, Stripe refund API, refund notifications |
| Dietary / meal tracking | ✅ | Configurable `meal_options`, RSVP dropdown, dietary summary API |
| Scheduled RSVP reminders | ✅ | `SendRsvpRemindersJob` (daily) + manual remind buttons |
| Household RSVP grouping | ✅ | CSV import, household filter, table columns on guests page |
| Custom confirmation messages | ✅ | `confirmation_settings` on events; emails read custom copy |

### Tier 2 — Planned (medium effort)

| Feature | Status |
|---------|--------|
| Visual seating chart | ⬜ |
| Badge layout editor | ⬜ |
| Email broadcast to attendees | ⬜ |
| Bulk resend to non-responders | 🟨 Partial — per-guest and remind-all exist; no dedicated non-responder UI |

### Tier 3 — Planned (new work)

| Feature | Status |
|---------|--------|
| Conditional form fields | ⬜ |
| Multi-page registration forms | ⬜ |
| Printable seating plans | ⬜ |
| Guest lookup (public check-in search) | ⬜ |

## Event Module Status

Per-event organizer modules and their current build state.

| Module | Status |
|--------|--------|
| Details | ✅ |
| Theme | ✅ |
| Landing page | ✅ |
| Settings (RSVP, confirmation emails, SEO) | ✅ |
| Tickets | ✅ |
| Form builder | ✅ |
| Coupons | ✅ |
| Check-in | ✅ |
| Analytics | ✅ |
| Agenda (tracks / sessions) | ✅ |
| Speakers | ✅ |
| Sponsors | ✅ |
| Exhibitors | ✅ |
| Guests (invite / RSVP) | ✅ |
| Seating (list-based assignment) | ✅ |
| Orders / refunds | 🟨 Partial |
| Visual seating chart | ⬜ Tier 2 |

## Recommended Priority (current)

| Priority | Item | Rationale |
|----------|------|-----------|
| 1 | Phase 27 — Templates & A11Y | Last builder-hardening phase |
| 2 | Phase 11 — Production readiness | Required before real traffic |
| 3 | Tier 2 — Visual seating, badge editor, email broadcast | Completes gala/wedding flows |
| 4 | Phase 6 finish — PayMongo, additional gateways | If PH / multi-gateway market matters |
| 5 | Phase 13 — Organizer billing | Monetize the platform |

## How To Update This File

Every phase's Definition of Done requires listing deferred items in this
file with a reason. When a phase completes, flip its status here and move
any "deferred to later" note from that phase's summary into the table
above if it isn't already tracked.
