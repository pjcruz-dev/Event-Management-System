# THEME_ENGINE.md

## What This Covers

The organizer-facing customization system for an event's public page —
distinct from the platform's own `DESIGN_SYSTEM.md`. This is
user-generated configuration, validated server-side, rendered on public
event pages.

## `theme_config` Shape

```json
{
  "primary_color": "#4f46e5",
  "secondary_color": "#0f172a",
  "font": "inter",
  "logo_url": "https://...",
  "hero_image_url": "https://...",
  "layout_variant": "classic | minimal | bold"
}
```

Rendered on the public event page as CSS custom properties
(`--event-primary`, `--event-secondary`, etc.) scoped to that page only —
never leaking into the dashboard shell's own design system.

### Layout variants

`layout_variant` drives a root `data-layout-variant` attribute and
`event-landing--{variant}` class on the event page wrapper. Three variants
ship in Phase 23:

| Variant | Character |
|---------|-----------|
| `classic` | Gradient hero with dot pattern, alternating muted sections, rounded cards with light shadow (default) |
| `minimal` | Flat primary hero, border-separated sections, understated typography, no card shadows |
| `bold` | Stronger gradient hero, larger headings, heavier card borders/shadows, high-contrast CTAs |

Variant styles live in `frontend/src/lib/event-layout-variant.ts` and are
consumed by `PublicEventLanding` only.

### Typography

Theme fonts (`Inter`, `Roboto`, `Open Sans`, `Lato`, `Merriweather`) are
loaded via `next/font/google` in `frontend/src/lib/event-theme-fonts.ts`.
The active font class is applied on the event page root only — dashboard
typography is unchanged. Merriweather falls back to `Georgia, serif`; all
others fall back to `system-ui, sans-serif`.

### Theme assets

Logo and hero uploads use `POST /api/v1/events/{event}/assets`. Removal uses
`DELETE /api/v1/events/{event}/assets/{type}` where `type` is `logo` or
`hero`. The Theme tab shows thumbnails, replace, and remove actions; upload
responses include the updated `theme_config` with public URLs.

### Draft preview links

Organizers can generate a signed preview token for draft or non-public
events:

- `POST /api/v1/events/{event}/preview-token` — create or rotate (default TTL
  from `config/event_builder.php`, 7 days)
- `GET /api/v1/events/{event}/preview-token` — active token metadata
- `DELETE /api/v1/events/{event}/preview-token` — revoke

Public preview page: `/e/{slug}/preview?token=...` (frontend) backed by
`GET /api/v1/public/events/{slug}/preview?token=...`. Returns 404 without a
valid token. Preview URLs use `noindex` robots metadata.

### Shared renderer

`PublicEventLanding` is the **single** renderer for:

- Public event pages (`/e/{slug}`)
- Dashboard Live preview (`EventPreview` wraps it with `mode="preview"`)

`mode="preview"` disables navigation on register/agenda/external links
(visual fidelity only). Never maintain a separate preview block switch.

## `landing_page_config` Shape

```json
{
  "blocks": [
    {
      "type": "hero",
      "visible": true,
      "settings": {
        "headline": "",
        "subheadline": "",
        "show_register_button": true
      }
    },
    {
      "type": "about",
      "visible": true,
      "settings": { "body": "<p>Rich HTML (sanitized on save)</p>" }
    },
    {
      "type": "agenda-preview",
      "visible": true,
      "settings": {
        "title": "Conference agenda",
        "subtitle": "",
        "limit": 4,
        "show_view_all_link": true
      }
    },
    {
      "type": "speakers-preview",
      "visible": true,
      "settings": {
        "title": "Featured speakers",
        "subtitle": "",
        "limit": 6,
        "layout": "grid"
      }
    },
    {
      "type": "sponsors",
      "visible": true,
      "settings": {
        "title": "Our sponsors",
        "subtitle": "",
        "group_by_tier": true
      }
    },
    {
      "type": "exhibitors",
      "visible": true,
      "settings": {
        "title": "Exhibitors",
        "subtitle": "",
        "limit": 12,
        "layout": "grid"
      }
    },
    { "type": "faq", "visible": true, "settings": { "title": "FAQ", "item_count": "0" } },
    {
      "type": "cta",
      "visible": true,
      "settings": {
        "headline": "",
        "subheadline": "",
        "button_label": "Register now"
      }
    }
  ],
  "tickets": {
    "visible": true,
    "title": "Get your tickets",
    "position": "after_blocks"
  }
}
```

Each block supports `visible` (default `true`). When `false`, the block is
skipped on the public page and preview. Page-level `tickets` controls whether
the ticket list appears after blocks and its section title.

About block `body` HTML is sanitized server-side (`LandingPageHtmlSanitizer`)
to an allowlist of tags (`p`, `strong`, `em`, `a`, `ul`, `ol`, `li`, `br`).
The builder uses a lightweight rich-text editor; the public renderer outputs
sanitized HTML with `prose` classes.

Block types extend over time — new block types must be added to both the
builder UI's block palette and the public renderer's block-type switch in the
same change, never one without the other.

## Validation

Both configs are validated server-side against a defined shape (a custom
Laravel Rule class functioning as the Zod-equivalent on the backend) —
a malformed frontend payload can never corrupt the stored config. The
frontend builder and backend validator must be kept in sync manually;
there's no shared schema file between the two stacks, so a block-type
change requires updating both.

## Builder UX

Drag-and-drop or up/down reordering (dnd-kit pairs well with shadcn/ui),
live preview pane reflecting changes before save. Preview renders through
the same component the public page uses — never a separate "preview-only"
renderer that can drift from what actually ships.
