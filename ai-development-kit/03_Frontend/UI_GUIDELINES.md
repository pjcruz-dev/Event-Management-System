# UI_GUIDELINES.md

## Baseline Requirements (every screen)

- Dark mode via Tailwind's `class` strategy — every component tested in
  both modes, not just built and assumed to work.
- Fully responsive down to small mobile widths.
- WCAG AA basics: labeled form fields, visible focus states, full
  keyboard navigation, sufficient color contrast, no interaction that
  depends on color alone.

## Layout Conventions

- `(auth)`, `(dashboard)`, `(public)` route groups — each with its own
  layout shell (Phase 0). Dashboard screens assume an authenticated,
  tenant-resolved user; public screens never assume auth.
- Dashboard information density: collapse to key metrics + "view
  details" on mobile rather than cramming every chart/table — called out
  explicitly for the analytics screens (Phase 9), applies platform-wide.

## Loading / Empty / Error States

Every data-fetching screen defines all three explicitly:
- Loading — skeleton matching the eventual layout, not a generic spinner
  for anything non-trivial.
- Empty — a real empty state with a next action ("Create your first
  event"), not a blank table.
- Error — a retry affordance, not just a toast that disappears.

## Forms

React Hook Form + Zod; see `FORMS.md` for the full convention including
the dynamic form builder.

## Feedback

Toasts for transient confirmations (saved, exported, invited); confirm
dialogs for destructive/irreversible actions (delete, archive, refund)
with the consequence stated in the dialog copy, not just "Are you sure?".

## Public-Surface Extra Care

Public pages (`(public)/*`) are the highest-traffic, least-controlled-
audience surface — extra attention to Core Web Vitals, accessibility,
and graceful degradation for slow connections (see
`03_Frontend/DASHBOARDS.md` for the dashboard side, Phase 10 for public
performance specifics).
