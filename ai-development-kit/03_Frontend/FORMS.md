# FORMS.md

## Standard Stack

React Hook Form + Zod for every form, static or dynamic. Zod schema is
the single source of truth for client-side shape/validation; server-side
Form Requests validate independently and are authoritative — the client
schema is a UX convenience, never trusted as the security boundary.

## Static Forms

Standard pattern: Zod schema → `useForm({ resolver: zodResolver(schema)
})` → shared `FormField` component (label + error + input) per field →
submit handler calls the typed `api-client` wrapper.

## Dynamic Form Builder (Phase 5)

Organizer-defined registration forms are data, not code:

```json
{
  "fields": [
    {
      "type": "text | email | phone | select | checkbox | textarea",
      "label": "Company",
      "required": true,
      "options": []
    }
  ]
}
```

The public registration form renders this array into inputs at runtime —
no per-event custom React code. A matching Zod schema is generated
client-side from the field definitions for immediate feedback; the
backend independently builds its own validation from the same field
definitions server-side (Phase 5's requirement: never trust the
frontend's validation alone).

## Multi-Step Flows

Registration (`(public)/events/[slug]/register`) and other multi-step
flows keep state in a single form-level object (React Hook Form's
built-in multi-step pattern or a light Zustand store for cross-step
state), not scattered `useState` per step — each step validates its own
slice before advancing, final submit validates the whole shape again.

## Error Display

Field-level errors inline under the field via `FormField`; form-level
errors (e.g. a 422 the client schema didn't catch, or a business-rule
rejection like "coupon expired") surface as a banner at the top of the
form, not swallowed into a generic toast.

## Accessibility

Every input has an associated `<label>` (not just a placeholder), error
messages are linked via `aria-describedby`, required fields are marked
both visually and via `aria-required`.
