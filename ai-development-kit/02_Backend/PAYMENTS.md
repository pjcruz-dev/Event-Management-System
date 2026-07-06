# PAYMENTS.md

## Gateway Interface

```php
interface PaymentGatewayInterface
{
    public function createCheckoutSession(Order $order): PaymentSession;
    public function handleWebhook(Request $request): PaymentEvent;
    public function refund(Payment $payment, ?int $amount = null): RefundResult;
}
```

`PaymentGatewayFactory` resolves the concrete implementation from the
organization's configured provider(s), stored on `Organization.settings`.
Adding a new gateway (PayPal, Maya, Xendit — all Later per `ROADMAP.md`)
means implementing this interface; it must never require touching order/
registration business logic.

## Providers (current)

- **`StripeGateway`** — Stripe Checkout Sessions (redirect-based).
- **`PayMongoGateway`** — card + GCash via PayMongo payment intents/
  sources.

Both verify webhook signatures before trusting any payload — see
`SECURITY.md`.

## Order/Payment Status State Machine

```
Order:      pending_payment -> paid -> (partially_refunded) -> refunded
                            -> expired (hold released, no payment)
Payment:    pending -> succeeded -> refunded / partially_refunded
                     -> failed
```

- `pending_payment`: reservation hold active, quantity decremented,
  awaiting webhook confirmation.
- `paid`: webhook confirmed, invoice generated, confirmation email
  queued, `OrderPaid` event fired.
- `expired`: hold expired before payment (scheduled job), quantity
  released back to the ticket type.
- `partially_refunded`: an organizer-initiated partial refund succeeded;
  order remains otherwise fulfilled.
- `refunded`: full refund processed, credit note generated.

## Refunds

Organizer-initiated, full or partial. Calls the order's gateway's
`refund()`, updates `Order`/`Payment` status per the state machine above,
generates a credit note on the `Invoice`. Every refund action is logged
to `payments.log` regardless of outcome.

## Transaction Records

`Payment` model stores gateway, external reference id, amount, currency,
status, and a raw payload snapshot for audit/debugging — see
`SECURITY.md` on redacting anything card-adjacent before persisting.

## Adding A New Gateway (checklist)

1. Implement `PaymentGatewayInterface`.
2. Verify webhook signature before any processing.
3. Map the provider's webhook events onto the state machine above —
   don't invent a new status value per gateway.
4. Add the provider to `PaymentGatewayFactory`'s resolution list and to
   `(dashboard)/settings/payments`.
5. No card data ever touches the backend directly (hosted checkout only).
