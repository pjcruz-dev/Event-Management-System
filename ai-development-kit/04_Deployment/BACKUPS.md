# BACKUPS.md

## Database

- **Frequency** — automated daily full backup at minimum; if the managed
  MySQL provider supports point-in-time recovery (binlog-based), enable
  it so recovery isn't limited to the last daily snapshot.
- **Retention** — keep daily backups for at least 30 days, weekly for 90
  days (adjust to actual compliance/contractual needs once known).
- **Storage** — backups stored in a separate region/provider from the
  primary database where feasible, so a provider-level incident doesn't
  take out both the live DB and its backups.

## Restore Procedure

1. Identify the target restore point (latest daily backup, or a
   point-in-time target if PITR is enabled).
2. Restore into a **new**, isolated database instance first — never
   restore directly over the live production database.
3. Run the application's migration status check against the restored
   copy to confirm schema consistency with the expected migration state
   at that point in time.
4. Spot-check tenant isolation on the restored copy (a quick query
   confirming `organization_id` scoping still holds) before considering
   the restore validated.
5. Only after validation, cut over (repoint `DB_HOST`/credentials) or
   selectively export the specific data that needed recovering.

## What's NOT Covered By DB Backups

- S3-stored files (avatars, logos, tickets, exports) — rely on the
  storage provider's own versioning/redundancy; if the provider doesn't
  offer it, add a separate periodic sync job to a backup bucket.
- Redis — treated as ephemeral (queue/cache state); nothing in Redis
  should be the sole source of truth for data that matters if lost.

## Testing Restores

A restore procedure that's never been tested is not a real backup
strategy. Schedule a periodic (e.g. quarterly) test restore into a
throwaway environment to confirm the process above actually works, not
just that backup files exist.

## Ownership

State who is responsible for verifying backups ran successfully and who
executes a restore in an incident — a backup strategy with no named
owner tends to silently stop working unnoticed.
