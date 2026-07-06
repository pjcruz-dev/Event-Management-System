# PHASE 12 — AI & Enterprise Features

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phases 0–11 (this phase touches nearly every domain — events,
> registration, dashboard, notifications — and requires a stable, deployed
> base to layer AI and platform-extensibility features onto).

## Objective

Add the AI-native and enterprise-extensibility features that differentiate
this platform from a straight Cvent/Eventbrite clone: an AI assistant for
organizers, AI content generation, a lightweight recommendation engine,
webhooks, a public API, and a minimal plugin system.

## Backend Tasks

### AI Provider Abstraction
- `AiProviderInterface` with a small contract: `generateText(string $prompt,
  array $context = []): AiResponse`, `generateStructured(string $prompt,
  array $schema): array`. Concrete implementation targets the Anthropic API
  (Claude) via a `ClaudeProvider`; design so a second provider could be
  swapped in without touching callers.
- All AI calls go through a queued job where the caller doesn't need a
  synchronous response (e.g. bulk bio generation); synchronous calls (chat
  assistant) go through a rate-limited endpoint with a sane per-organization
  daily quota, enforced at the `SubscriptionPlan` level from Phase 11's
  feature-flag mechanism.
- Every AI call is logged (prompt hash, token count, cost estimate, org id)
  to a dedicated `ai_usage.log` channel for cost tracking and abuse
  detection — never logs full prompt content containing PII beyond what's
  needed for debugging.

### AI Event Assistant
- `POST /events/{event}/ai/assistant` — conversational endpoint scoped to
  one event's context (its registrations, ticket sales, agenda), answering
  organizer questions like "how many VIP tickets are left" or "draft a
  reminder email for tomorrow's session change." Read-only against the
  organizer's own tenant-scoped data only — the assistant must never be
  able to answer with another organization's data, and never executes a
  mutating action without an explicit confirm step.

### AI Content Generators
- `POST /events/{event}/ai/landing-page` — generates a first-draft
  `landing_page_config` (Phase 4 shape) from a short organizer prompt
  ("tech conference, 2 days, keynote-heavy"), returned for the organizer to
  edit in the existing builder, never auto-published.
- `POST /events/{event}/ai/email` — generates draft copy for a
  notification type (reminder, thank-you, cancellation), validated against
  the existing Notification templates (Phase 11) as a starting draft only.
- `POST /speakers/{speaker}/ai/bio` — generates a draft speaker bio from a
  few bullet points; organizer reviews/edits before saving.
- All generator endpoints return a draft; nothing is persisted until the
  organizer explicitly saves it through the normal Phase 4/8 CRUD
  endpoints — the AI layer never bypasses existing validation.

### Recommendation Engine
- `SessionRecommendationService` and `EventRecommendationService` — starts
  as a heuristic (same category/track, attendee's registered sessions'
  adjacent time slots, same-category "similar events" from Phase 10),
  upgraded here to incorporate lightweight collaborative signals (attendees
  who registered for X also registered for Y) computed as a scheduled
  batch job, not a real-time ML pipeline.
- Exposed via `GET /events/{event}/recommendations` (public) and
  `GET /attendees/me/recommended-sessions` (authenticated attendee).

### Webhooks (outbound)
- `WebhookSubscription` (tenant) — organization subscribes a URL to event
  types (`registration.created`, `order.paid`, `checkin.scanned`,
  `event.published`, etc.).
- Delivery via a queued job, HMAC-signed payload (organization's webhook
  secret), retry with exponential backoff, delivery log
  (`WebhookDelivery`: status, response code, attempt count) visible to the
  organizer for debugging.
- `(dashboard)/settings/webhooks` — subscription CRUD, delivery log viewer,
  "send test event" action.

### Public API
- A versioned, API-key-authenticated (separate from Sanctum user tokens)
  read/write surface for events, registrations, and check-ins, scoped to
  the issuing organization only, rate-limited per key, documented via
  OpenAPI spec generated from the existing route/Resource definitions.
- `ApiKey` model (tenant) — scoped permissions per key (read-only vs
  read-write), revocable, last-used timestamp for the organizer to spot
  stale/compromised keys.

### Plugin System (minimal, intentionally scoped down)
- A registry pattern (`PluginManager`) allowing a small, defined set of
  extension points (a new landing-page block type, a new notification
  channel, a new payment gateway) to be registered by a plugin package
  without modifying core files — this phase ships the registry and one
  reference plugin as a working example, not a full marketplace.
- Explicitly out of scope: a plugin marketplace UI, third-party plugin
  vetting/sandboxing, billing for premium plugins — log these as follow-ups
  in `ROADMAP.md` rather than half-building them.

## Frontend Tasks

- `(dashboard)/events/[id]/ai-assistant` — chat-style panel, scoped to the
  event, with clear "draft, not sent/published" affordances on any
  generated content.
- AI-assist buttons integrated into existing builders: a "Generate with AI"
  option inside the Phase 4 landing-page builder and Phase 8 speaker form,
  never a separate disconnected flow.
- `(dashboard)/settings/webhooks`, `(dashboard)/settings/api-keys` — CRUD
  screens per the backend above.
- `(public)/events/[slug]` — recommendation strip upgraded from Phase 10's
  heuristic-only version to use the new recommendation endpoint.

## Security & Cost Controls

- Per-organization daily AI request quota tied to subscription plan
  (Phase 11 feature flags) — a free-tier org cannot run unbounded AI
  generation and drive unbounded cost.
- Public API keys are rate-limited and scoped read-only by default;
  write-scoped keys require explicit organizer opt-in with a warning about
  what write access allows.
- Webhook URLs are validated to reject internal/private IP ranges
  (SSRF protection) before a subscription is saved.

## Tests

- AI assistant cannot answer with another organization's data even when
  prompted to try (adversarial prompt test).
- Webhook delivery: valid subscription receives a correctly HMAC-signed
  payload; a failing endpoint retries per the backoff policy and is marked
  failed after max attempts, visible in the delivery log.
- Public API: a read-only key cannot perform a write; a key from Org A
  cannot read Org B's data even with a guessed resource id.
- Recommendation batch job produces deterministic, tenant-correct output
  against seeded data.

## Definition of Done

- An organizer can generate a landing page draft, a speaker bio draft, and
  chat with the event assistant about their own event's data — full round
  trip through the actual UI.
- A webhook subscription fires correctly on a real event (e.g. order paid)
  and is visible in the delivery log.
- A public API key can list an organization's events and register a
  webhook, verified against a live request, not just a unit test.
- List every file created or modified.
