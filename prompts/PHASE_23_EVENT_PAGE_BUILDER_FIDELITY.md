# PHASE 23 — Event Page Builder: Preview Fidelity & Layout Variants

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 4 (theme/landing builder), 8 (speakers/sponsors/agenda
> data for dynamic blocks), 10 (public event pages). Safe to run after Phase
> 22 if RSVP is already built — no hard dependency on 22.

## Objective

Fix the **trust gap** between the organizer's Live preview and the public
event page. Today `layout_variant` is stored but never rendered, and the
dashboard uses a separate `EventPreview` component that diverges from
`PublicEventLanding`. Organizers must see **exactly** what attendees will see.

**Design principle (from `THEME_ENGINE.md`):** one renderer for public pages
and builder preview — never maintain two block renderers.

Document changes in `ai-development-kit/03_Frontend/THEME_ENGINE.md`.

---

## Backend Tasks

No schema changes required unless you discover a validation gap.

- Confirm `ThemeConfigRule` still accepts `layout_variant` values
  `classic`, `minimal`, `bold` — no change needed if already correct.
- No new endpoints for this phase.

---

## Frontend Tasks

### 1. Shared landing renderer

- Refactor so **one component tree** renders landing blocks for:
  - `(public)/e/[slug]` via `PublicEventLanding`
  - `(dashboard)/events/[id]/edit` Live preview pane
- Accept a `mode: "public" | "preview"` (or equivalent) prop only where
  behavior must differ (e.g. register links are non-navigating in preview,
  or `Link` becomes `<span>` / `button type="button"`).
- **Delete or reduce** `EventPreview` to a thin wrapper around the shared
  renderer — do not keep duplicate `PreviewBlock` switch logic.
- Preview must include the same sections the public page shows when
  relevant: tickets block, default CTA fallback, footer — not a stripped
  skeleton.

### 2. Layout variants (make `layout_variant` real)

Implement three visually distinct layouts applied via CSS classes / layout
tokens scoped to the event page root (never leak into dashboard shell):

| Variant | Intent |
|---------|--------|
| `classic` | Current default: gradient hero, alternating section backgrounds, rounded cards |
| `minimal` | Flat backgrounds, thin borders, less gradient, more whitespace, understated typography |
| `bold` | High-contrast hero, larger headings, stronger color fills, more dramatic CTA sections |

- Read `event.theme_config.layout_variant` in the shared renderer.
- Changing layout in the Theme tab must update Live preview **before save**
  (preview already merges unsaved `themeConfig` — preserve that).
- Layout variant must affect hero, section shells, and CTA styling at
  minimum.

### 3. Theme tab ↔ preview wiring

- Ensure logo and hero image from unsaved `themeConfig` appear in preview
  (not only after save).
- Font family from theme applies in preview the same way as on the public
  page (full font loading is Phase 24 — use the same `fontFamily` hook now).

### 4. Edit page integration

- Replace `EventPreview` usage in `events/[id]/edit/page.tsx` with the
  shared renderer + preview mode.
- Keep existing TanStack Query fetches for speakers/sponsors/sessions when
  blocks need them — move fetch orchestration to a small hook if needed.

---

## Tests

### Frontend (Vitest / component tests)

- Layout variant class or data-attribute is applied for each of
  `classic`, `minimal`, `bold`.
- Shared renderer renders hero headline from landing block settings.

### Backend

- No new backend tests required unless you touch validation.

### Manual / Definition of Done checks

- Open Theme tab → change layout variant → preview updates immediately.
- Compare preview side-by-side with published public URL — block order,
  hero, tickets, and CTA must match (modulo non-interactive links in preview).

---

## Out of Scope (later phases)

- Google Fonts loading (Phase 24)
- Logo/hero thumbnail UI (Phase 24)
- Draft preview share link (Phase 25)
- Mobile viewport toggle (Phase 25)
- New block types or rich text (Phase 26)
- Theme presets (Phase 27)

---

## Definition of Done

- `layout_variant` visibly changes the public page and Live preview.
- Live preview uses the same block renderer as `/e/{slug}` — no duplicate
  `PreviewBlock` switch.
- `THEME_ENGINE.md` updated to describe layout variants and the shared
  renderer approach.
- List every file created or modified.
