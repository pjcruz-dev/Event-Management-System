# PHASE 13 — Organizer Billing & Subscriptions

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 6 (payment gateway interface exists), Phase 11
> (`SubscriptionPlan` model and feature-flag mechanism already exist —
> this phase adds the actual charge, Phase 11 deliberately stopped short
> of it).

## Objective

Charge organizations for their subscription plan. Phase 11 built the
mechanism (plans, feature gating); this phase makes plans actually cost
money, on a recurring billing cycle, using the same gateway abstraction
built in Phase 6 rather than inventing a second payment system.

## Backend Tasks

### Plan & Pricing
- Extend `SubscriptionPlan` (Phase 11) with `price`, `currency`,
  `billing_interval` (`monthly|annual`), `trial_days`, and a
  `gateway_price_id` per configured gateway (Stripe Price ID, PayMongo
  equivalent) — the plan's feature-gating logic from Phase 11 is
  untouched; this phase only adds what it costs.
- `PlanChangeService` — handles upgrade (immediate, prorated) and
  downgrade (takes effect at the end of the current billing period, not
  immediately, to avoid mid-cycle feature yanking).

### Subscription Lifecycle
- `Subscription` (tenant) — organization_id, plan_id, gateway,
  gateway_subscription_id, status (`trialing|active|past_due|canceled`),
  current_period_end.
- Reuses `PaymentGatewayInterface` from Phase 6 where the gateway supports
  recurring billing natively (Stripe Billing, PayMongo's recurring
  equivalent) — extend the interface with
  `createSubscription(Organization $org, SubscriptionPlan $plan):
  SubscriptionSession` and `cancelSubscription(Subscription $sub): void`
  rather than building a parallel gateway layer.
- Webhook handlers (extending Phase 6's signature-verified webhook
  pattern) process `invoice.paid`, `invoice.payment_failed`,
  `subscription.updated`, `subscription.canceled` — updating
  `Subscription.status` and, on `past_due`, degrading the organization to
  the free/default plan's feature set after a documented grace period
  rather than an abrupt cutoff.

### Dunning
- Failed-payment retry follows the gateway's native retry schedule where
  available (Stripe Smart Retries); a queued reminder email to the
  organization owner on each failed attempt, escalating in urgency, using
  Phase 11's notification templates pattern.

### Invoicing (platform → organizer)
- Distinct from Phase 6's `Invoice` model (which is organizer → attendee).
  A new `PlatformInvoice` (tenant) — one per billing cycle, downloadable
  PDF, listed in `(dashboard)/settings/billing`.

## Frontend Tasks

- `(dashboard)/settings/billing` — current plan, usage against plan
  limits (ties into Phase 11's feature flags — e.g. "3 of 5 events used
  this month" if the plan caps event count), upgrade/downgrade CTA,
  payment method management (via the gateway's hosted portal, e.g. Stripe
  Customer Portal — never build a custom card-entry form), invoice/
  receipt history.
- Plan comparison/pricing page component, reusable between the dashboard
  billing settings and the public marketing site (Phase 17).
- Past-due banner surfaced prominently in the dashboard shell when a
  subscription is `past_due`, with a direct link to update payment method.

## Security

- Subscription webhook endpoints follow the exact signature-verification
  requirement from Phase 6 — no exceptions for "it's just a billing
  event."
- Platform-level billing data (`Subscription`, `PlatformInvoice`) is
  tenant-scoped like everything else — an organization can only ever see
  its own billing history.

## Tests

- Successful subscription checkout transitions `Subscription.status` to
  `active` and grants the plan's feature flags immediately.
- Failed payment transitions to `past_due`; after the documented grace
  period without resolution, feature flags degrade to the free tier —
  test both the grace-period-still-active and grace-period-expired cases.
- Downgrade takes effect at period end, not immediately; upgrade is
  immediate and correctly prorated.
- Webhook replay/duplicate delivery does not double-charge or duplicate
  `PlatformInvoice` rows (idempotency key check).

## Definition of Done

- An organization can subscribe to a paid plan, get charged on a real
  test-mode recurring cycle, see the correct features unlock, and cancel
  — full round trip through the actual UI, not just API-level.
- `BILLING.md` documents the subscription state machine, dunning policy,
  and grace-period rule.
- List every file created or modified.
