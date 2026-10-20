# 0015. The last save wins

- Status: accepted
- Date: 2026-10-20

## Context

Two administrators can edit the configuration of the same channel at the same time. Detecting a
conflicting save would take optimistic locking, so a version column and a schema change, for a
screen that is rarely edited by two people at once. Two of these races end differently: two first
saves of a channel, or of a service row added in between, both try to insert the same unique row.

## Decision

There is no locking. The form posts every field, so the last save of an existing configuration
overwrites the previous one without a warning. When an insert collides with a row another
administrator has just created, the save is refused with a message and the page shows their
version, instead of an error page.

## Consequences

- No version column, and no conflict screen to maintain.
- Concurrent edits of an existing configuration lose the first one's changes silently.
- Adding locking later is a schema change, so a new migration.

## Related

- [0012. Service rows are seeded and never removed](0012-service-rows-seeded-never-removed.md)
- [0014. A schema change is a new migration, written against the DBAL Schema API](0014-schema-change-is-a-new-migration.md)
