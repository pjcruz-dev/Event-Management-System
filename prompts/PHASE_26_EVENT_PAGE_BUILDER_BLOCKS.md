# PHASE 26 — Event Page Builder: Block Enhancements

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 23–25, Phase 8 (speakers, sponsors, agenda, exhibitors
> data). Exhibitors module must exist before the exhibitors block ships.

## Objective

Upgrade the **Landing page** tab from a minimal block list to a
configurable page builder: richer content, per-block settings, exhibitors
support, and control over the tickets section.

Update `ai-development-kit/03_Frontend/THEME_ENGINE.md` and backend
`LandingPageConfigRule` in sync.

---

## Backend Tasks

### Extend `landing_page_config` schema

**Per-block common fields** (add to each block object):

```json
{
  "type": "speakers-preview",
  "visible": true,
  "settings": {
    "title": "Our speakers",
    "subtitle": "",
    "limit": 6,
    "layout": "grid"
  }
}
```

- `visible` (bool, default `true`) — when false, skip rendering on public
  page and preview.
- Block-specific settings (validate in `LandingPageConfigRule`):

| Block | New settings |
|-------|----------------|
| `hero` | existing headline/subheadline + optional `show_register_button` (bool, default true) |
| `about` | `body` supports HTML string (sanitized server-side) |
| `agenda-preview` | `title`, `limit` (1–20), `show_view_all_link` (bool) |
| `speakers-preview` | `title`, `limit` (1–24), `layout`: `grid` \| `list` |
| `sponsors` | `title`, `group_by_tier` (bool, default true) |
| `exhibitors` | `title`, `limit` (1–48), `layout`: `grid` \| `list` |
| `faq` | `title` (optional override) |
| `cta` | `headline`, `subheadline`, `button_label` |

**Page-level settings** (sibling to `blocks`):

```json
{
  "blocks": [ ... ],
  "tickets": {
    "visible": true,
    "title": "Tickets",
    "position": "after_blocks"
  }
}
```

- `tickets.position`: `after_blocks` (default) \| `hidden` — no "before
  hero" in this phase.

### Exhibitors block

- New block type `exhibitors` — reads from existing exhibitors API /
  model (Phase 8 or later exhibitor module).
- If exhibitors module is not implemented, **stop and report** — do not
  invent models.

### HTML sanitization

- Sanitize `about` body HTML on save (Laravel: `strip_tags` with allowlist
  or `HTMLPurifier` — allow `p`, `strong`, `em`, `a`, `ul`, `ol`, `li`,
  `br` only).

---

## Frontend Tasks

### Landing page builder UI

- Per-block **visibility toggle** (eye icon) — sets `visible: false`.
- Section title/subtitle fields for dynamic blocks (agenda, speakers,
  sponsors, exhibitors, faq, cta).
- Numeric `limit` inputs with min/max from schema.
- Layout select for speakers/exhibitors where applicable.
- **Tickets section** panel at bottom of builder: show/hide + custom title.
- Duplicate block action (copies block with same settings).
- Empty-state hints unchanged for blocks with no data.

### Rich text for About

- Introduce a lightweight rich text editor (e.g. Tiptap or similar) for
  `about` block body only — must output HTML matching backend allowlist.
- Public renderer renders sanitized HTML with `prose` classes.

### Exhibitors block

- Add to block palette, builder card, shared renderer, and preview.
- Match public styling to sponsors grid/list patterns.

### Shared renderer updates

- Respect `visible` on all blocks.
- Respect `tickets` page-level config (hide section when `visible: false`).
- Apply per-block titles in `SectionHeading`.

---

## Tests

### Backend

- Invalid `limit` or `layout` rejected by `LandingPageConfigRule`.
- About body with `<script>` stripped on save.
- `exhibitors` block validates when type present.

### Frontend

- Visibility toggle excludes block from preview output.
- Tickets hidden when `tickets.visible` is false.

---

## Out of Scope

- Venue/map block, countdown, gallery, video embeds (Phase 27 or later)
- Drag-and-drop from block palette sidebar (current add-button UX is fine)
- Per-block background color overrides

---

## Definition of Done

- Dynamic blocks have configurable titles and limits in the builder UI.
- About block supports basic rich text; XSS vectors are blocked server-side.
- Exhibitors appear as a landing block when exhibitor data exists.
- Tickets section can be hidden or retitled from the Landing tab.
- Frontend Zod schema and `LandingPageConfigRule` stay in sync.
- List every file created or modified.
