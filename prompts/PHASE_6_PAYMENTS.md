# PHASE 6 — Payment Module

> Paste `00_MASTER_PROMPT.md` above this prompt before sending to Cursor.
> Depends on: Phase 5 (Order exists in `pending_payment` status).

## Objective

Build a gateway-agnostic payment layer, then implement Stripe and
PayMongo (GCash flows through PayMongo) as concrete providers. Design so
adding PayPal/Maya/Xendit later doesn't require touching business logic.

## Backend Tasks

### Payment Interface
- `PaymentGatewayInterface` with a small, deliberate contract:
  `createCheckoutSession(Order $order): PaymentSession`,
  `handleWebhook(Request $request): PaymentEvent`,
  `refund(Payment $payment, ?int $amount = null): RefundResult`.
- `PaymentGatewayFactory` resolves the correct implementation from the
  organization's configured provider (an organization can choose which
  gateway(s) it accepts — store this on `Organization.settings`).

### Providers (this phase)
- `StripeGateway` implements the interface using Stripe Checkout Sessions
  (redirect-based, simplest to keep consistent across providers).
- `PayMongoGateway` implements the interface for card + **GCash** via
  PayMongo's payment intents / sources API.
- Both providers' webhook handlers verify signatures before trusting the
  payload — never process an unverified webhook.

### Order → Payment Flow
- On successful webhook confirmation: mark `Order` and `Registration` as
  `paid`, release nothing (quantity was already decremented at
  reservation time in Phase 5), generate an `Invoice` record, queue the
  "your registration is confirmed" email (ticket/QR content still comes
  from Phase 7 — for now, confirm payment received).
- On failed/expired webhook or hold expiry: release the reserved
  quantity back to the ticket type (ties into Phase 5's expiry job).

### Refunds
- Organizer-initiated refund endpoint (full or partial), calls the
  correct gateway's `refund()`, updates `Order`/`Payment` status,
  generates a credit note on the `Invoice`.

### Transaction Records
- `Payment` model (tenant) — gateway, external reference id, amount,
  currency, status, raw payload snapshot (for auditing/debugging).
- `payments.log` channel (from Phase 0) logs every webhook received and
  every refund action, regardless of outcome.

## Frontend Tasks

- Checkout step in the public registration flow (continuing from
  Phase 5): "Pay with card" / "Pay with GCash" buttons that redirect to
  the gateway's hosted checkout, then a return page that polls order
  status until the webhook has landed (with a sensible timeout and a
  "we'll email you" fallback message).
- `(dashboard)/events/[id]/orders` — organizer order list with payment
  status, filter by status, manual refund action with confirm dialog and
  reason field.
- `(dashboard)/settings/payments` — organization-level gateway
  configuration (which providers are enabled, API keys stored securely
  server-side, never exposed to the frontend).

## Security Requirements

- Webhook endpoints are public but MUST verify the provider's signature
  before doing anything. Log and reject unsigned/invalid-signature
  requests without processing them.
- Never store raw card data — Stripe/PayMongo hosted checkout means the
  platform never touches card numbers directly; confirm this is true of
  the implementation before considering the phase done.
- API keys/secrets read from env/config, never hardcoded, never returned
  in any API response.

## Tests

- Webhook signature verification: valid signature processes the order;
  invalid signature is rejected and logged, order untouched.
- Successful payment: order transitions `pending_payment` → `paid`,
  invoice generated.
- Refund: full refund updates order/payment status and generates a
  credit note; partial refund leaves the order in a `partially_refunded`
  state if you model it that way — state your status enum explicitly in
  `PAYMENTS.md`.

## Definition of Done

- A test/sandbox-mode payment through both Stripe and PayMongo completes
  end to end from the frontend checkout button to a confirmed order.
- `PAYMENTS.md` documents the gateway interface, the order/payment status
  state machine, and how to add a new gateway.
- List every file created or modified.
