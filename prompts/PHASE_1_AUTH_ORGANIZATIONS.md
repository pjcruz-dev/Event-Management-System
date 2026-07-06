# PHASE 1 — Authentication & Organizations

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 0 (folder structure, ApiResponse helper, api-client).

## Objective

Build the complete authentication and organization-membership module —
the foundation every other phase's authorization depends on.

## Backend Tasks

### Models & Migrations
- `User` (extend defaults: `avatar_path`, `phone`, `timezone`, `locale`,
  `email_verified_at`, soft deletes)
- `Organization` (`name`, `slug`, `logo_path`, `owner_id`, `settings` JSON,
  soft deletes)
- `organization_user` pivot (`role`, `status: invited|active|suspended`,
  `invited_by`, timestamps)
- `Invitation` (`organization_id`, `email`, `role`, `token`, `expires_at`,
  `accepted_at`)

### Auth (Sanctum)
- Register, Login, Logout endpoints, token-based (SPA or bearer — pick
  bearer for a multi-client API used by web + future mobile).
- Email verification flow (signed URL, resend endpoint).
- Password reset flow (token, expiry, rate-limited).
- `/api/v1/me` endpoint returning the authenticated user plus their
  organizations and role in each.

### Roles & Permissions (Spatie Permission)
- Seed baseline roles per organization: `owner`, `admin`, `member`, plus
  support for **custom roles** defined per organization (not just the
  three defaults).
- Permissions should be granular (e.g., `events.create`, `events.publish`,
  `members.invite`, `settings.manage`) rather than one blanket
  "admin can do everything" check.
- `OrganizationPolicy`, `InvitationPolicy` enforcing role-based access.

### Invitations
- Invite-by-email endpoint (owner/admin only), generates signed token,
  sends `InvitationMail`.
- Accept-invitation endpoint — creates the `organization_user` row if the
  invited email matches an existing user, or carries the invitation
  through registration if it's a new user.
- Revoke/resend invitation endpoints.

### Middleware
- `EnsureUserBelongsToOrganization` — resolves `organization_id` from the
  route or header and 403s if the user isn't a member.
- `EnsureUserHasRole` / permission-based middleware alias.

## Frontend Tasks

- `(auth)/login`, `(auth)/register`, `(auth)/forgot-password`,
  `(auth)/reset-password` — forms with React Hook Form + Zod, calling the
  `api-client` from Phase 0.
- Auth state via a Zustand store (`useAuthStore`) hydrated from
  `/api/v1/me` on load; route guards in the `(dashboard)` route group that
  redirect unauthenticated users to `/login`.
- `(dashboard)/settings/profile` — profile edit + avatar upload (uses the
  `StorageService` from Phase 0 on the backend).
- `(dashboard)/settings/organization` — organization details, member list,
  invite member modal, role management, pending invitations list.
- Organization switcher component (for users belonging to multiple orgs).

## Validation & Authorization

- Every mutating endpoint has a Form Request class — no inline
  `$request->validate()`.
- Every organization-scoped endpoint checks a Policy — no manual
  `if ($user->role === 'admin')` checks scattered in controllers.

## Tests

- Feature tests: register, login, logout, password reset, email
  verification, invite flow (invite → accept → member appears), role
  permission enforcement (member cannot invite, admin can).
- At least one test asserting a user from Organization A cannot see or
  act on Organization B's data via these endpoints.

## Definition of Done

- A new user can register, verify email, create an organization
  (becoming its owner), invite a teammate, and the teammate can accept
  and log in — all end to end through the actual UI, not just the API.
- `AUTH.md` written documenting the flows above and the permission list.
- List every file created or modified.
