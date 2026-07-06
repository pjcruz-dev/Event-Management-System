# PHASE 25 — Event Page Builder: Draft Preview & Builder UX

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 23–24 (shared renderer, theme assets). Phase 11
> recommended for production email/notifications patterns but not blocking.

## Objective

Let organizers **preview and share** draft events safely, preview **mobile
layouts**, and avoid losing unsaved builder work — without exposing drafts
publicly.

---

## Backend Tasks

### Draft preview tokens

- Add `preview_tokens` table (or equivalent): `id`, `event_id` (FK,
  `onDelete(' cascade')`), `organization_id`, `token` (unique, opaque,
  indexed), `expires_at` (nullable), `created_by` (user FK), `revoked_at`
  (nullable), `last_used_at` (nullable), timestamps.
- `POST /api/v1/events/{event}/preview-token` — creates or rotates token
  (organizer only). Default expiry: 7 days (configurable via
  `config/event_builder.php`).
- `DELETE /api/v1/events/{event}/preview-token` — revoke active token.
- `GET /api/v1/events/{event}/preview-token` — return active token metadata
  (not the raw token if already shown once — or return token for dashboard
  display; document choice).

### Public preview route (frontend SSR + optional API)

- `(public)/e/[slug]/preview?token=...` — renders draft/archived events
  **only** when token is valid; otherwise 404 (do not leak event existence).
- Banner at top: "Preview mode — not published" (non-dismissable).
- `noindex` meta robots on preview URLs.
- Log `last_used_at` on successful token validation (throttle updates if
  needed).

### Token validation

- Service class: `ValidateEventPreviewTokenAction` — checks token, event
  match, not revoked, not expired.
- Tenant-scoped: token's `organization_id` must match event.

---

## Frontend Tasks

### Draft preview in edit page

- When event is not publicly visible, show **"Copy preview link"** (enabled
  when user has update permission) instead of only the disabled Public view
  button — or show both with clear labels.
- Generate/regenerate preview token via API; copy to clipboard with toast.
- "Open preview" opens `/e/{slug}/preview?token=...` in new tab.

### Viewport toggle

- On Live preview card: **Desktop / Tablet / Mobile** toggle (CSS width
  constraints: e.g. 100%, 768px, 375px) with smooth transition.
- Preview content scrolls inside the constrained frame; does not affect
  dashboard layout.

### Unsaved changes guard

- Track dirty state for: Details form, `themeConfig`, `landingConfig`,
  Settings/RSVP local state.
- `beforeunload` warning when dirty.
- Confirm dialog when switching tabs (Details / Theme / Landing / Settings)
  with unsaved builder changes.
- Optional: subtle "Unsaved changes" badge near Save buttons.

### Preview mode polish

- Register / agenda links in preview mode: show as disabled or
  `aria-disabled` with tooltip "Preview only" — do not navigate away from
  dashboard context when preview is inline; new-tab preview may use real
  links with banner.

---

## Tests

### Backend

- Valid token → draft event preview data returned.
- Expired / revoked / wrong slug → 404.
- Org B cannot create token for Org A's event.
- Published public event still works without token at `/e/{slug}`.

### Frontend

- Viewport toggle applies expected max-width class.
- Dirty state triggers confirm on tab switch (unit test or RTL).

---

## Out of Scope

- Password-protected public events (separate feature)
- Preview analytics
- Email "send preview to client"

---

## Definition of Done

- Organizer can copy a shareable link to preview a **draft** event.
- Preview URLs are not indexable and return 404 without a valid token.
- Mobile/tablet viewport toggle works in Live preview.
- Unsaved changes are guarded on tab switch and page leave.
- List every file created or modified.
