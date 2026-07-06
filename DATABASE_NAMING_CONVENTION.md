# DATABASE_NAMING_CONVENTION.md

Database naming rules for MySQL 8 via Laravel migrations. Full indexing and
relationship guidance lives in `ai-development-kit/02_Backend/DATABASE_GUIDELINES.md`.

## Tables

- **snake_case**, **plural** nouns: `events`, `ticket_types`, `order_items`
- Pivot tables: alphabetical singular pair — `organization_user`
- Named pivot models when the pivot carries data: `EventSpeaker` → table
  `event_speaker` (singular model name, explicit migration)

## Models

- **PascalCase**, **singular**, matching the table's entity: `Event`,
  `TicketType`, `OrderItem`
- Eloquent `$table` property only when the table name is irregular

## Columns

| Pattern | Example |
|---|---|
| Foreign keys | `{singular_table}_id` → `organization_id`, `event_id` |
| Booleans | `is_` / `has_` prefix → `is_published`, `has_capacity_limit` |
| Timestamps (beyond defaults) | explicit, typed → `published_at`, `checked_in_at` |
| JSON columns | `{purpose}_config` or `{purpose}_data` → `theme_config` |
| Enums / status | `{noun}_status` or bare `status` with documented values |

## Indexes

Every table with `organization_id` gets:

1. Index on `organization_id` alone
2. Composite index on hot filter paths, e.g. `(organization_id, status)`

Every foreign key defines an explicit `onDelete` behavior in the migration
with a comment explaining the choice (cascade vs restrict vs null).

## Multi-Tenancy

Every tenant-owned business table includes `organization_id`. Isolation is
enforced by the `BelongsToTenant` trait (Phase 2), not by naming alone.

## Soft Deletes

Applied where undo/audit matters: `events`, `orders`, `registrations`,
`organizations`, `users`. Not applied to lookup/config tables or bare pivots.

## Migration File Names

Laravel timestamp prefix + descriptive snake_case:

```
2026_07_02_000001_create_events_table.php
```

## Seeders / Factories

- Factories: `{Model}Factory` in `database/factories/`
- Seeders: `{Purpose}Seeder` in `database/seeders/`
