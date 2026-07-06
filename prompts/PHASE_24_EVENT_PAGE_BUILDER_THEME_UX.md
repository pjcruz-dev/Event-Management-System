# PHASE 24 — Event Page Builder: Theme Assets & Typography

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 23 (shared renderer and layout variants). Phase 4
> storage/upload endpoints for logo and hero must exist.

## Objective

Make the **Theme tab** feel complete: organizers can see, replace, and
remove branding assets, and selected fonts actually render on public pages
and in the Live preview.

---

## Backend Tasks

### Asset removal

- Add `DELETE /api/v1/events/{event}/builder-assets/{type}` where `type`
  is `logo` or `hero` (match existing upload route naming if different).
- Authorized via `EventPolicy` (same as upload).
- Clears `theme_config.logo_url` or `theme_config.hero_image_url`, deletes
  the stored file via `StorageService`, returns updated event in standard
  envelope.
- Validate `type` is one of allowed values; 422 on unknown type.

### Upload improvements (if missing)

- Ensure upload endpoint returns the updated `theme_config` in the response
  so the frontend can sync without a full refetch.
- Enforce max file size and mime types server-side (images only) — align
  with existing `StorageService` limits.

---

## Frontend Tasks

### Theme builder asset UI

In `ThemeBuilder` (or extracted subcomponents):

- **Thumbnail preview** for current logo and hero (when URL is set).
- **Replace** — file input still works; show filename or "Replace" label
  when asset exists.
- **Remove** — button calls DELETE endpoint, clears local state, shows
  empty state.
- **Upload state** — disabled inputs + spinner or progress text while
  uploading; inline error message on failure (use `ApiError` message).
- Accessible labels: logo alt text is event name on public page — no new
  field required this phase.

### Google Fonts (or equivalent)

- Load the selected theme font on public event pages and in dashboard
  preview when it is not a system font.
- Supported fonts must match `FONT_OPTIONS` in
  `frontend/src/features/events/constants.ts` and `ThemeConfigRule` on
  the backend.
- Use `next/font/google` where possible for performance; scope font to the
  event page root only — do not change dashboard typography.
- Fallback chain: selected font → `system-ui, sans-serif` (or serif for
  Merriweather).

### Settings tab cross-link (small UX)

- Add a short helper line on the Theme tab: "SEO and social preview image
  are configured under Settings" with no navigation refactor required.

---

## Tests

### Backend (PHPUnit)

- Upload logo → DELETE logo → `logo_url` is null and file removed from
  storage (fake disk).
- Unauthorized user cannot delete another org's assets (403/404).
- Invalid asset type returns 422.

### Frontend

- ThemeBuilder shows thumbnail when `logo_url` / `hero_image_url` present.
- Remove button clears URL in local state after successful DELETE.

---

## Out of Scope

- Image crop / focal point
- OG image management on Theme tab (stays on Settings)
- Draft preview URL (Phase 25)
- Custom font uploads

---

## Definition of Done

- Organizer can upload, see, replace, and remove logo and hero from the
  Theme tab without guessing whether upload worked.
- All five theme fonts render correctly on `/e/{slug}` and Live preview.
- List every file created or modified.
