# COMPONENT_LIBRARY.md

## Folder Structure

```
components/
├── ui/        # shadcn/ui primitives — Button, Input, Dialog, etc.
└── shared/    # composed, reusable, product-specific components
features/
└── <domain>/  # one folder per business domain (events, tickets,
               #  checkin, dashboard, exhibitor-portal, ...), populated
               #  as its phase lands. Domain-specific components,
               #  hooks, and API bindings live here, not in components/.
```

## Rule Of Thumb

- Used by 2+ features, no domain knowledge baked in → `components/shared/`.
- Domain knowledge baked in (knows about `Event`, `Registration`,
  `TicketType`, etc.) → `features/<domain>/components/`.
- Pure shadcn/ui primitive, minimally modified → `components/ui/`.

## Naming

`PascalCase.tsx` per component, one component per file except for tiny
tightly-coupled sub-components. Co-locate a component's Storybook story
or test file (if used) next to it, not in a parallel tree.

## Composition Over Configuration

Prefer composable components (children/slots) over components with a
growing prop list of booleans controlling internal behavior — a
`<Card>` with `<CardHeader>`/`<CardBody>` children rather than a `<Card
showHeader showFooter variant="x">` prop pile.

## Reused Across Phases

Some components are built once (typically Phase 0/1) and reused
everywhere after: `EmptyState`, `ConfirmDialog`, `StatusBadge`,
`DataTable` (paginated, sortable — used by orders, registrations,
activity log, exhibitor leads), `FormField` wrapper (label + error +
input, used by every React Hook Form instance). Don't recreate these
per-feature — extend the shared one.
