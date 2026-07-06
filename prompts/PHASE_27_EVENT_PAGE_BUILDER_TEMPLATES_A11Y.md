# PHASE 27 — Event Page Builder: Templates & Accessibility

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 23–26 (full builder stack). Optional: Phase 22 for
> wedding/RSVP templates in preset content.

## Objective

Help organizers **start fast** with event-type templates and theme presets,
and catch **accessibility issues** before publish — especially color
contrast on custom themes.

---

## Backend Tasks

### Event page templates (starter configs)

- Add `config/event_page_templates.php` (or DB table if you prefer
  seeding — config file is enough for v1) defining named templates:

| Template key | Audience | Includes |
|--------------|----------|----------|
| `conference` | B2B events | theme preset + default block order with agenda/speakers/sponsors |
| `wedding` | Social | warm palette + hero/about/faq/cta; minimal ticket emphasis |
| `webinar` | Online | minimal layout + about/agenda/cta |
| `fundraiser` | Nonprofit | bold layout + sponsors/cta |
| `blank` | Power users | empty blocks, default theme only |

- `POST /api/v1/events/{event}/apply-template` — body: `{ "template": "conference" }`.
  - Only allowed when event is `draft` OR query param `force=true` with
    confirm (organizer must pass `{ "confirm": true }` to overwrite
    published config).
  - Merges `theme_config` and `landing_page_config` from template;
    does not change event name, dates, or slug.
- Validate template key; 422 on unknown.

### Contrast check (server-side helper)

- `POST /api/v1/events/{event}/theme-contrast-check` — body: optional
  `theme_config` override for unsaved checks.
- Return WCAG AA pass/fail for:
  - primary on white (CTA buttons)
  - white on primary gradient hero (approximate)
  - secondary as accent on white
- Use relative luminance formula (no external API). Return structured
  `{ checks: [{ pair, ratio, passes_aa }] }`.

---

## Frontend Tasks

### Apply template flow

- On **Landing** or **Theme** tab (or both): "Start from template"
  dropdown with short descriptions.
- Confirm dialog when overwriting existing blocks/theme.
- After apply, preview updates; user still must click Save (respect dirty
  state from Phase 25).

### Theme presets

- Curated palette presets (6–8): e.g. Corporate Blue, Wedding Blush,
  Festival Bold, Minimal Mono.
- One click sets `primary_color`, `secondary_color`, and optionally
  `layout_variant` + `font` — user can tweak after.
- Presets are frontend constants; no new DB table.

### Accessibility panel

- Collapsible **"Accessibility"** section on Theme tab:
  - Run contrast check on current (possibly unsaved) colors.
  - Show pass/fail with ratio; link to WCAG docs in helper text.
  - Non-blocking: warn, do not prevent save (organizers may intentionally
    use brand colors) — but **block Publish** if any check fails unless
    `event.settings.allow_low_contrast_theme === true` (add to
    `settings` JSON with default false) OR show confirm on publish only.

Choose one publish-gating approach and document in `THEME_ENGINE.md`.

### SEO helper (lightweight)

- On Settings tab: character count for meta title/description; warn when
  over recommended lengths (60 / 160).

---

## Tests

### Backend

- Apply template on draft event updates configs.
- Apply on published without confirm → 422.
- Contrast check returns expected pass for `#1E40AF` on white.

### Frontend

- Template apply populates blocks in builder state.
- Contrast UI shows fail state for low-contrast pair (e.g. `#FFFF00` on white).

---

## Out of Scope

- Marketplace of third-party templates
- AI-generated landing copy (Phase 12)
- Custom CSS injection
- Full axe-core CI scan (optional follow-up in Phase 19)

---

## Definition of Done

- Organizer can apply a starter template to a draft event in one flow.
- Theme presets speed up color selection.
- Contrast checker runs on Theme tab and surfaces WCAG AA results.
- Publish gating or confirm behavior is documented and tested.
- List every file created or modified.
