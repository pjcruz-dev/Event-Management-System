# PHASE 18 — Legal & Compliance

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 1 (User/Organization data exists to govern), Phase 6
> (payment data exists), Phase 13 (billing data exists). Should be done
> before accepting real payments from real organizers, not after.

## Objective

This phase is intentionally different from the others: it's primarily
document drafting plus a small number of concrete technical flows
(consent capture, data export, data erasure). Cursor should draft the
legal documents as clearly-marked templates requiring actual legal
review — Cursor is not a lawyer and this phase's Definition of Done does
not include "legally sufficient," only "structurally complete and
technically wired up."

## Documents To Produce (as real files, clearly marked DRAFT)

- `TERMS_OF_SERVICE.md` — platform usage terms, organizer obligations,
  liability limitations, termination conditions. Mark prominently at the
  top: *"DRAFT — requires review by a licensed attorney in your
  jurisdiction before publishing."*
- `PRIVACY_POLICY.md` — what data is collected (per the actual schema
  from Phase 3: user profile, registration data, payment metadata,
  activity logs), why, retention periods, third parties data is shared
  with (payment gateways, email provider, AI provider if Phase 12 is
  live), user rights (access/export/delete).
- `COOKIE_POLICY.md` — cookies/local-storage actually used by the
  frontend (auth token storage, analytics if any), categorized
  (necessary vs. optional) to support a real consent banner.
- `DATA_PROCESSING_ADDENDUM.md` — template DPA for organizations that
  need one to process their attendees' data through the platform
  (relevant if selling to EU-based organizers under GDPR).

## Backend Tasks

### Consent Capture
- `ConsentLog` (tenant-adjacent, tied to `User`) — records what policy
  version was accepted, when, and by what action (registration,
  policy-update re-acceptance). Required so "we have proof of consent"
  isn't just a checkbox with no audit trail.
- Policy version bump triggers a re-consent requirement on next login for
  existing users — don't silently assume continued consent to updated
  terms.

### Data Export (GDPR/CCPA "right to access")
- `POST /account/data-export` — queued job compiles the requesting user's
  data (profile, registrations, orders, activity log entries where
  they're the actor) into a downloadable archive, delivered via a
  signed, expiring URL (reuses Phase 0/11's storage + signed-URL pattern)
  and a notification when ready.

### Data Erasure ("right to be forgotten")
- `POST /account/deletion-request` — does not hard-delete synchronously.
  Flags the account for deletion, sends a confirmation-required email
  (prevents accidental/malicious instant deletion), then a queued job
  performs deletion after the confirmation window:
  - PII is deleted or irreversibly anonymized on `User` and
    `Registration` records.
  - Financial records required for tax/legal retention (`Order`,
    `Payment`, `Invoice`) are retained per the retention period stated in
    `PRIVACY_POLICY.md`, but with attendee PII stripped/anonymized where
    the retained record doesn't legally require it.
  - Organizations (not individual users) are not deletable via this
    self-service flow — that goes through the super-admin panel (Phase
    14) with its own review step, since deleting an org affects other
    members and attendees.

## Frontend Tasks

- Consent banner (cookie categories from `COOKIE_POLICY.md`), blocking
  non-essential cookies/scripts until accepted, per what's actually in
  use — don't ship a generic banner disconnected from the real cookie
  audit.
- `(dashboard)/settings/privacy` — data export request button, deletion
  request flow with the confirmation step made unmissable, link to
  current ToS/Privacy Policy with the accepted version/date shown.
- Registration/signup flow requires explicit ToS/Privacy acceptance
  (checkbox, not pre-checked) before account creation completes.

## Tests

- Data export produces a complete, correctly-scoped archive (only the
  requesting user's own data, verified against seeded fixtures).
- Deletion request is not immediate — confirms the confirmation-window
  behavior, and confirms retained financial records are correctly
  anonymized rather than either fully deleted or left with PII intact.
- Policy version bump correctly forces re-consent on next authenticated
  request for a user who accepted an older version.

## Definition of Done

- All four documents exist, clearly marked as requiring legal review.
- A user can request and receive a data export, and submit and confirm a
  deletion request, through the actual UI — full round trip.
- List every file created or modified.
- Explicit note in `ROADMAP.md`: legal review of the drafted documents by
  a qualified attorney remains an outstanding manual step regardless of
  how complete the technical implementation is.
