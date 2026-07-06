# PHASE 16 — Internationalization & Multi-Currency

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 4 (Event model), Phase 5 (TicketType/pricing), Phase 9
> (dashboard numbers currently assume a single currency and must be
> audited here).

## Objective

Retrofit locale-aware text and true multi-currency support. Everything
built in Phases 0–11 is implicitly English/single-currency — this phase
finds every place that assumption is baked in and makes it configurable,
without a rewrite.

## Backend Tasks

### Currency
- `TicketType.currency` (already exists per Phase 3) is enforced, not
  just stored: an event can offer ticket types in a defined base currency;
  changing an event's currency after tickets have sold is blocked (would
  corrupt historical order totals) — surface a clear error instead of
  silently allowing it.
- All monetary values stored as integer minor units (cents), never
  floats — audit Phase 5/6/9 for any float-based money math introduced
  before this phase and correct it here if found.
- Dashboard/reporting (Phase 9) aggregates are computed per-currency, not
  summed across currencies into a misleading single number — an
  organization running events in both USD and PHP sees two totals, not
  one blended (wrong) figure, unless an explicit FX-converted rollup is
  requested and clearly labeled as converted-at-time-of-report.
- Payment gateways (Phase 6) are confirmed to actually support each
  currency an organizer selects — reject at ticket-type-creation time if
  the org's configured gateway doesn't support the chosen currency, not
  at checkout time.

### Localization (i18n)
- Laravel's localization for backend-generated strings (validation
  messages, email templates, notification content) — locale resolved from
  the requesting user's `User.locale` (already a Phase 1 field) or an
  `Accept-Language` header for guest/public requests.
- Translatable content model: event-facing text the *organizer* writes
  (event name, description, landing page block copy) is organizer-authored
  and not machine-translated by default — a `TranslatedField` pattern
  (JSON column keyed by locale, e.g. `{ "en": "...", "es": "..." }`) lets
  an organizer optionally provide multiple locales for their own content;
  falls back to the default locale if a requested one isn't provided.

### Frontend Tasks

- `next-intl` (or equivalent App-Router-compatible i18n library) wired
  into the Next.js app; all platform UI strings (buttons, labels, error
  messages — not organizer content) extracted to locale files.
- Currency formatting via `Intl.NumberFormat`, locale- and currency-aware,
  used everywhere a price renders — no manual `$` string concatenation
  anywhere in the codebase; audit and fix any found from earlier phases.
- Locale switcher in the dashboard shell and public site footer.
- Public event pages render in the visitor's requested locale where the
  organizer provided a translation, falling back gracefully and clearly
  (not silently mixing languages mid-page) when they didn't.

## Scope Boundary

This phase does not build machine translation (no auto-translate button —
that would be an AI Enterprise feature, Phase 12, if wanted later). It
makes the *system* locale/currency-aware; content translation is a manual
organizer input this phase enables, not automates.

## Tests

- Ticket purchase in a non-USD currency completes with correct minor-unit
  math end to end (no floating-point rounding drift across order →
  payment → invoice).
- Dashboard revenue total for an org with events in two currencies shows
  two distinct totals, not one incorrectly summed number.
- A public event page with only an English translation, viewed with a
  Spanish `Accept-Language` header, falls back to English visibly and
  correctly rather than showing a broken partial mix.

## Definition of Done

- An organizer can create an event priced in a non-default currency, sell
  a ticket, and see it reported correctly on the dashboard; the platform
  UI itself renders in at least one additional locale end to end.
- List every file created or modified, including every float-based money
  calculation found and corrected from earlier phases.
