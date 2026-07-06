# DESIGN_SYSTEM.md

## Foundation

shadcn/ui primitives (`components/ui/`) + Tailwind CSS, dark mode via
class strategy. Phase 0 wires the theme tokens; no custom color
decisions are made in that phase — this file is where those decisions
get recorded once made.

## Tokens (fill in as decided, keep this file authoritative)

> **Status:** Intentionally empty pre-build. Phase 0 wires Tailwind/shadcn
> plumbing only; record concrete token values here during Phase 0 frontend
> setup or the first UI phase that needs brand decisions (typically Phase 1
> auth screens). Do not treat missing values as doc drift.

- **Color** — primary/secondary/accent, semantic colors (success,
  warning, destructive, info), each with a light- and dark-mode value.
- **Typography** — a single font family for UI, an optional display font
  for public marketing surfaces; a defined type scale (e.g. `text-xs`
  through `text-4xl` mapped to specific use cases: labels, body, section
  headers, page titles).
- **Spacing** — Tailwind's default scale, used consistently; avoid
  arbitrary pixel values in components.
- **Radius / elevation** — one consistent corner-radius scale and shadow
  scale, not ad-hoc per component.

## Organizer-Facing Theming vs. Platform Design System

Two distinct things, don't conflate them:
- This file governs the platform's own UI — dashboard, auth screens,
  admin surfaces.
- `THEME_ENGINE.md` governs what an *organizer* can customize on their
  public event pages (`theme_config`), which is user-generated and
  intentionally more flexible/less constrained than the platform's own
  design system.

## Component Ownership

- `components/ui/` — shadcn/ui primitives, minimally modified from
  upstream.
- `components/shared/` — composed, reusable pieces specific to this
  product (e.g. a `StatusBadge`, `ConfirmDialog`, `EmptyState`).
- `features/<domain>/` — domain-specific components that aren't reused
  outside that feature.

## Dark Mode

Every token above has a dark-mode value; components never hardcode a
light-only color. Verified per-screen, not just per-component in
isolation, since composition can reveal contrast issues single
components don't show.
