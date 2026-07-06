# Discover Search — SQL Today, Scout Tomorrow

Phase 10 ships discover search with indexed SQL filters on `events`:

- `category` (exact match)
- `q` keyword (`LIKE` on `name`, `description`, `venue`)
- date range (`starts_at` / `ends_at`)
- location (`venue` `LIKE`)
- pricing (`is_free`, min/max ticket price subqueries)

This is sufficient for MVP volumes (thousands of published events) when
combined with pagination and sensible DB indexes.

## When to upgrade

Move to **Laravel Scout** + **Meilisearch** (or Algolia) when:

- Full-text relevance ranking matters more than exact filters
- `LIKE '%keyword%'` scans become slow at scale
- You need typo tolerance, synonyms, or faceted search beyond SQL

## Upgrade checklist (not implemented)

1. `composer require laravel/scout meilisearch/meilisearch-php`
2. Add `Searchable` trait to `Event`; index only `published` + `public` events
3. Sync index on publish/update/archive via model observers or queued jobs
4. Replace `DiscoverEventService` keyword path with Scout `search()` while
   keeping SQL filters for category/date/price where Meilisearch filters apply
5. Add Meilisearch to Docker Compose and production env (`SCOUT_DRIVER`,
   `MEILISEARCH_HOST`, `MEILISEARCH_KEY`)
6. Backfill: `php artisan scout:import "App\Models\Event"`

Until then, keep `DiscoverEventService` as the single discover entry point so
the API contract stays stable when the driver changes.
