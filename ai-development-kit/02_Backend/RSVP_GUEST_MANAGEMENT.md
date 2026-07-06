# RSVP & Guest Management

Phase 22 adds invite-first guest management for galas, weddings, and dinners without replacing open conference registration.

## Registration modes

| Mode | Value | Behavior |
|------|-------|----------|
| Open | `open` | Default. Public `/e/{slug}/register` works for everyone. |
| Invite only | `invite_only` | Public register requires `?invitation_token=` on register API. |
| RSVP | `rsvp` | Guests respond via `/rsvp/{token}`; register also requires token. |

Existing events default to `open`. Switching to `invite_only` does not invalidate confirmed open registrations.

## RSVP settings (`events.rsvp_settings` JSON)

| Key | Type | Default |
|-----|------|---------|
| `allow_plus_ones` | bool | `false` |
| `max_plus_ones_per_invite` | int (0–10) | `0` |
| `collect_meal_preferences` | bool | `false` |
| `allow_maybe_response` | bool | `true` |
| `response_deadline` | ISO datetime or null | `null` |

Meal preferences are stored in `registrations.custom_fields.meal_preference` when enabled.

## Models

### `GuestInvite`

Tenant-scoped invite record. Tracks lifecycle status and RSVP response.

- **Token:** opaque 64-char `invitation_token` (unique). Public URL: `{FRONTEND_URL}/rsvp/{token}`.
- **Response strategy:** declined/maybe are tracked on `guest_invites` only (no check-in-eligible `Registration`). Accepted guests create/update a `Registration` via `CreateRegistrationAction`.
- **Statuses:** `pending`, `sent`, `opened`, `responded`, `declined`, `expired`, `revoked`.

### `EventTable`

Seating tables with optional canvas coordinates (`x`, `y`, `rotation`).

### `Registration` extensions

- `rsvp_response`: `accepted` | `declined` | `maybe` | null
- `guest_invite_id`, `is_plus_one`, `primary_registration_id`, `table_id`

## Authorization

Guest invite and table management reuse **`events.manage`** / **`events.view`** (no separate permission). Policies: `GuestInvitePolicy`, `EventTablePolicy`.

## APIs

### Organizer (authenticated)

| Method | Path |
|--------|------|
| GET | `/api/v1/events/{event}/guest-invites` |
| POST | `/api/v1/events/{event}/guest-invites` |
| POST | `/api/v1/events/{event}/guest-invites/import` |
| PUT | `/api/v1/events/{event}/guest-invites/{invite}` |
| DELETE | `/api/v1/events/{event}/guest-invites/{invite}` |
| POST | `/api/v1/events/{event}/guest-invites/{invite}/send` |
| POST | `/api/v1/events/{event}/guest-invites/send-bulk` |
| GET/POST/PUT/DELETE | `/api/v1/events/{event}/tables` |
| GET | `/api/v1/events/{event}/seating` |
| PUT | `/api/v1/events/{event}/seating/assignments` |

### Public

| Method | Path |
|--------|------|
| GET | `/api/v1/public/rsvp/{token}` |
| POST | `/api/v1/public/rsvp/{token}/respond` |

Public event `GET /api/v1/public/events/{slug}` remains viewable without a token; ticket types are hidden until a valid `invitation_token` is supplied for `invite_only`/`rsvp` modes. `POST .../register` requires a valid token in those modes.

## CSV import format

Required column: `email`. Optional: `first_name`, `last_name`, `phone`, `tags` (comma-separated), `plus_one_limit`, `table` (table name). Template: `frontend/public/templates/guest-import-template.csv`.

Import returns `{ created, skipped, errors[] }` per row without failing the whole batch.

## Exports

New report types: `guest-invites`, `seating` (CSV/XLSX). Dashboard summary includes RSVP metrics: invited, sent, accepted, declined, maybe, response rate, plus-one count.

## Notifications (queued)

- `GuestInvitationNotification`
- `RsvpReminderNotification`
- `RsvpResponseConfirmationNotification`

## Frontend routes

| Route | Purpose |
|-------|---------|
| `/events/[id]/guests` | Guest list, import, send |
| `/events/[id]/seating` | Tables + list-based assignment |
| `/rsvp/[token]` | Public RSVP page |

Event edit **Settings** tab includes registration mode and RSVP settings.

## Security notes

- Tokens are unguessable (64 random chars).
- Revoked/expired/past-deadline tokens return 404 on public RSVP.
- All organizer endpoints are tenant-scoped; public lookups use `withoutTenantScope` with token binding only.
