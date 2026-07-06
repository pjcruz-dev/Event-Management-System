# AUTH.md

Authentication and organization membership for the Event Management SaaS platform.
Implemented in Phase 1.

## Flows

### Registration
`POST /api/v1/auth/register`

Creates a `User`, hashes the password, optionally accepts an `invitation_token`
(to join an organization during signup), fires the `Registered` event, sends
email verification, and returns a Sanctum bearer token.

### Login / Logout
- `POST /api/v1/auth/login` — validates credentials, returns bearer token.
- `POST /api/v1/auth/logout` — revokes the current bearer token
  (`PersonalAccessToken::findToken()`).

Tokens are device-scoped via `device_name` (frontend sends `web`).

### Email verification
- Signed link emailed to the user points to the frontend
  `/verify-email?verification_url=...`, which calls the API:
  `GET /api/v1/auth/email/verify/{id}/{hash}` (signed middleware).
- `POST /api/v1/auth/email/resend` — authenticated, rate-limited.

### Password reset
- `POST /api/v1/auth/forgot-password` — rate-limited, always returns success
  message (no email enumeration).
- `POST /api/v1/auth/reset-password` — validates token + email + new password.
- Reset emails link to `{FRONTEND_URL}/reset-password?token=...&email=...`.

### Current user
`GET /api/v1/me` — returns the authenticated user and their active organizations
(with role per org). The frontend `useAuthStore` hydrates from this on load.

### Organizations
- `GET /api/v1/organizations` — list organizations the user belongs to (active).
- `POST /api/v1/organizations` — create organization; creator becomes `owner`.
- `GET/PUT /api/v1/organizations/{id}` — view/update (policy + `org.member` middleware).
- `GET /api/v1/organizations/{id}/members` — list members.
- `PUT /api/v1/organizations/{id}/members/{user}` — update member role.
- `GET/POST /api/v1/organizations/{id}/roles` — list/create custom roles.

### Invitations
- `POST /api/v1/organizations/{id}/invitations` — owner/admin (`members.invite`).
- `GET /api/v1/organizations/{id}/invitations` — pending invitations.
- `DELETE /api/v1/organizations/{id}/invitations/{invitation}` — revoke.
- `POST /api/v1/organizations/{id}/invitations/{invitation}/resend` — resend.
- `GET /api/v1/invitations/{token}` — public preview (requires registration?).
- `POST /api/v1/invitations/{token}/accept` — authenticated accept.

Invitation emails link to `{FRONTEND_URL}/register?invitation_token={token}`.

### Profile
- `PUT /api/v1/profile` — update name, email, phone, timezone, locale.
- `POST /api/v1/profile/avatar` — multipart upload (max 2 MB, image types).

## Roles & Permissions

Baseline roles per organization (Spatie Permission with `teams` enabled;
`organization_id` is the team key):

| Role | Permissions |
|---|---|
| `owner` | All permissions (`*`) |
| `admin` | All listed permissions except none withheld in Phase 1 |
| `member` | `events.view`, `orders.view`, `checkin.scan` |

Custom roles: `POST /api/v1/organizations/{id}/roles` with a name and permission
subset. Roles are scoped per organization.

### Permission list (Phase 1 seed)

`events.create`, `events.view`, `events.manage`, `events.publish`,
`members.invite`, `members.manage`, `settings.manage`, `orders.view`,
`orders.refund`, `checkin.scan`, `exhibitors.manage`, `reports.export`

Defined in `config/permissions.php`; role mappings in `config/roles.php`.

## Middleware

| Alias | Class | Purpose |
|---|---|---|
| `resolve.tenant` | `ResolveTenant` | On authenticated routes: resolves org from route param or `X-Organization-Id`; 403 if not a member; sets `TenantContext` |
| `tenant.required` / `org.member` | `RequireTenantContext` | Fails closed (422) when tenant context was not resolved |
| `permission:{name}` | `EnsureUserHasPermission` | Checks granular permission in current org context |

See `TENANCY.md` for the full isolation stack (`TenantScope`, `BelongsToTenant`, etc.).

## Policies

- `OrganizationPolicy` — view, update, manageMembers, inviteMembers, manageRoles
- `InvitationPolicy` — viewAny, create, delete, resend

## Frontend

| Route | Purpose |
|---|---|
| `/login`, `/register` | Auth forms (RHF + Zod) |
| `/forgot-password`, `/reset-password` | Password reset |
| `/verify-email` | Email verification callback |
| `/dashboard` | Overview + create organization |
| `/settings/profile` | Profile + avatar upload |
| `/settings/organization` | Org details, members, invites, roles |

`useAuthStore` (Zustand + persist) holds token and active organization id.
`(dashboard)/*` routes are wrapped in `AuthGuard`.

## Environment

| Variable | Purpose |
|---|---|
| `FRONTEND_URL` | Links in verification, reset, and invitation emails |
| `SANCTUM_STATEFUL_DOMAINS` | Not used for bearer-only API (Phase 1) |

## Tenant note

Phase 1 enforces **organization membership** on org-scoped routes. Full
`BelongsToTenant` global scoping arrives in Phase 2 — see `TENANCY.md`.
