# NOTIFICATIONS.md

## Base Convention

Laravel Notifications, one class per notification type, each declaring
its `via()` channels array explicitly — never a default guess. Database
notifications (in-app) are enabled by default alongside mail where it
makes sense for the recipient to see a history.

## Notification Types & Channels

| Notification | Channels | Queued |
|---|---|---|
| Registration confirmation | mail | yes |
| Payment receipt | mail | yes |
| Invitation | mail | yes |
| Password reset | mail | yes (short TTL) |
| Event reminder (scheduled, pre-event) | mail, database | yes |
| Refund processed | mail | yes |
| Export ready | database (+ mail if large/slow export) | yes |

## Templates

Phase 5/6 ship with placeholder content ("we've received your
registration, payment pending"). Phase 11 replaces these with final
branded templates for: registration confirmation, payment receipt,
invitation, password reset, event reminder. Ticket/QR content is only
added to the confirmation email once Phase 7 exists — don't build that
email twice.

## Branding

Templates pull organizer branding (logo, primary color) from the event's
`theme_config` where the notification is event-specific (registration
confirmation, reminder); platform-level notifications (invitation,
password reset) use the platform's own branding, not the organizer's.

## Delivery Confirmation

Mail failures are logged (`security.log` for auth-related mail,
`payments.log` for payment-related mail) rather than silently swallowed.
A future observability pass (Phase 11 monitoring hook) should alert on a
sustained delivery failure rate, not just log individual failures.
