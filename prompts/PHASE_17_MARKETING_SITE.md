# PHASE 17 — Product Marketing Site

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 1 (registration exists to link to), Phase 13 if
> pricing page should reflect real plans (recommended but not blocking —
> can ship with placeholder pricing and wire it up once Phase 13 lands).

## Objective

Build the site that sells the platform itself — distinct from Phase 10's
public *event* discovery pages, which sell individual organizers' events
to attendees. This phase is: homepage, features, pricing, signup funnel,
for prospective *organizers*.

## Scope Clarification (read this first)

Phase 10 built `(public)/discover` and `(public)/events/[slug]` — pages
about events, for attendees. This phase builds the platform's own
`(marketing)/...` route group — pages about the *product*, for
organizers deciding whether to sign up. Do not conflate the two; they
have different audiences, different SEO targets, and different content
ownership (Phase 10's content is organizer-authored per event; this
phase's content is the platform's own copy).

## Frontend Tasks

- `(marketing)/` — homepage: hero, feature highlights, social proof
  section (placeholder-structured for logos/testimonials until real ones
  exist), CTA to sign up.
- `(marketing)/pricing` — plan comparison table, pulling live plan/price
  data from Phase 13's `SubscriptionPlan` model if it exists, otherwise
  static placeholder tiers clearly marked as illustrative in a code
  comment so nobody forgets to wire it up later.
- `(marketing)/features` — feature deep-dive pages (or sections),
  organized around the platform's actual capabilities from Phases 4–12,
  not aspirational features that don't exist yet.
- `(marketing)/about`, `/contact` — standard company pages; contact form
  submits to a queued notification (reuses Phase 0's notification
  strategy) rather than a raw mailto link.
- Signup CTA throughout links directly into the Phase 1 `(auth)/register`
  flow — no separate marketing-site-only signup form that then needs
  reconciling with the real auth system.

## SEO

- Full server-rendered metadata per marketing page (title, description,
  OG image) — same rigor as Phase 10 applied to event pages, applied here
  to the platform's own pages, since this is the highest-value SEO
  surface for organic organizer acquisition.
- `sitemap.xml` entry for every marketing page, separate from Phase 10's
  event-sitemap (which only includes published events) — don't merge the
  two sitemaps into one confusing file; either two sitemap files or one
  sitemap index referencing both.

## Design

- Uses the platform's `DESIGN_SYSTEM.md` tokens but with more visual
  latitude than the dashboard (marketing sites typically want more
  expressive imagery/typography than a data-dense dashboard) — state any
  intentional deviation from the core design system in this phase's
  summary rather than silently drifting.
- Full responsive + dark mode + accessibility basics, same baseline as
  every other surface in the product (`UI_GUIDELINES.md`).

## Performance

- This is the platform's front door — Core Web Vitals matter as much
  here as on Phase 10's event pages. Static generation (not ISR/SSR)
  where content doesn't change per-request, since marketing pages are the
  same for every visitor.

## Tests

- Every marketing page returns correct server-rendered meta tags
  (verifiable via raw HTML fetch).
- Pricing page, if wired to Phase 13, reflects the actual active plans —
  a plan disabled/archived in the admin doesn't still show on the public
  pricing page.
- Contact form submission is validated server-side and doesn't allow
  unbounded spam submission (rate-limited, same class of protection as
  Phase 5's public registration endpoint).

## Definition of Done

- A prospective organizer can land on the homepage, review pricing,
  and sign up — landing directly in the real Phase 1 registration flow,
  full round trip.
- List every file created or modified.
