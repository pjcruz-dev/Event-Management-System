# STORAGE.md

## Disks

`config/filesystems.php` (Phase 0) defines three disks:
- `local` — server-local, non-public (e.g. temp processing files).
- `public` — server-local but web-accessible, used only in local dev.
- `s3` — S3 or S3-compatible object storage, the only disk actually used
  for user-facing uploads in staging/production.

`FILESYSTEM_DISK` env var controls which is active per environment (see
`ENVIRONMENT.md`). Phase 11 explicitly confirms production points at
`s3`, not silently still writing to local disk in a prod-like
environment.

## `StorageService` Wrapper

All later phases go through `StorageService`, never `Storage::` directly
— this keeps disk selection, path conventions, and validation in one
place rather than scattered per-feature.

## What's Stored

| Content | Path convention | Public? |
|---|---|---|
| User avatars | `avatars/{user_id}/...` | yes (public read) |
| Organization logos | `organizations/{org_id}/logo/...` | yes |
| Event theme assets (logo, hero image) | `events/{event_id}/theme/...` | yes |
| Exhibitor materials | `exhibitors/{exhibitor_id}/materials/...` | yes, scoped to the exhibitor's own event |
| Tickets, badges, certificates (generated PDFs) | `registrations/{registration_id}/...` | no — served via a signed/authenticated URL, not public |
| Export files (CSV/Excel/PDF reports) | `exports/{organization_id}/...` | no — signed URL, expiring |

## Validation

Every upload validates MIME type and size server-side before it reaches
`StorageService` (see `SECURITY.md`) — Phase 11 audits every upload path
introduced in earlier phases rather than assuming they were done
correctly.

## Signed URLs

Anything not meant for public consumption (tickets, exports) is served
via a short-lived signed URL, generated on request, never a permanently
public S3 path.
