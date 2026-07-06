# PHASE 21 — Installable PWA & Mobile Readiness

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 7 (offline QR check-in already exists — this phase
> generalizes offline/installable behavior platform-wide rather than
> just the scanner), Phase 10 (public site performance baseline).

## Objective

Phase 7 built offline resilience specifically for the check-in scanner.
This phase makes the whole frontend properly installable as a Progressive
Web App — home-screen install, offline shell, push notifications for
key events — without building a separate native app or React Native
codebase. A full native app remains explicitly out of scope for this
phase; document it as a future option in `ROADMAP.md` if/when justified
by real usage data.

## Frontend Tasks

### PWA Manifest & Install
- `manifest.json` — app name, icons (multiple sizes), theme color,
  `display: standalone`, start URL scoped appropriately per user type
  (an organizer's install should default to the dashboard, an attendee's
  to their tickets — if feasible, offer both as separate installable
  contexts, or default to the most common path and document the
  trade-off).
- Install prompt UX: a non-intrusive, dismissible "install this app"
  banner shown at a sensible trigger point (e.g. after a second visit,
  not on first page load) — never auto-triggering the native browser
  install prompt without user intent.

### Service Worker & Offline Shell
- A service worker caching the app shell (static assets, core routes) so
  the app opens to *something* useful when offline, rather than a browser
  error page — this is distinct from Phase 7's offline *data* queue
  (scans), which is a separate, already-solved concern; this phase is
  about the shell loading at all.
- Cache strategy: stale-while-revalidate for static assets, network-first
  for anything data-sensitive (never serve stale ticket/order data as if
  current) — explicit per-route-type strategy documented in
  `PWA.md`, not a single blanket caching rule applied everywhere.

### Push Notifications (optional per platform capability)
- Web Push for a defined, small set of high-value notifications only:
  event reminder (already exists as email, Phase 11 — push is an
  additional channel, not a replacement), check-in confirmation for
  attendees who opted in, organizer alerts for time-sensitive events
  (e.g. "your ticket sale is 90% sold out"). Opt-in only, never
  auto-subscribed.

### Responsive/Touch Audit
- Full audit pass across every dashboard and public screen built in
  Phases 1–20 for actual touch usability on real mobile viewports (not
  just a resized desktop browser) — tap target sizing, no
  hover-dependent-only interactions, scanner/camera flows (Phase 7)
  re-verified specifically on mobile Safari and Chrome, which have
  different camera-permission quirks.

## Backend Tasks

### Push Subscription Storage
- `PushSubscription` (belongs to `User`) — stores the browser's push
  endpoint/keys, tied to opt-in preferences per notification type from
  the list above.
- Notification dispatch (Phase 11's Notification classes) gains a `push`
  channel alongside `mail`/`database` for the specific notification types
  that support it, following the same "declare channels explicitly"
  convention from `NOTIFICATIONS.md`.

## Explicitly Out Of Scope

- A native iOS/Android app (React Native, Flutter, or platform-native) —
  not built here. If real usage data later justifies it, that's a
  separate, larger initiative outside this phase's scope.
- Background sync beyond what Phase 7 already built for check-in scans.

## Tests

- Manifest validates (installable per Lighthouse PWA audit criteria).
- Service worker correctly serves a cached shell when the network is
  disabled in a test, and correctly refetches fresh data once back
  online (no stale ticket/order data shown as current).
- Push subscription opt-in/opt-out correctly gates whether a notification
  is sent via the `push` channel.

## Definition of Done

- The app passes a Lighthouse PWA installability audit, installs
  correctly on at least one Android and one iOS test device, opens to a
  usable (if limited) shell when offline, and a test push notification is
  received end to end on an opted-in device.
- `PWA.md` documents the manifest configuration, caching strategy per
  route type, and the notification types wired to push.
- List every file created or modified.
