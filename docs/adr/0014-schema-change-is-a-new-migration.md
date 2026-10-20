# 0014. A schema change is a new migration, written against the DBAL Schema API

- Status: accepted
- Date: 2026-10-20

## Context

The plugin owns tables, and shops run it on MySQL, MariaDB or PostgreSQL. A migration written in SQL
would target one of them. Asking each shop to generate its own with a schema diff would mix the
plugin's tables with unrelated changes of the application, and leave every shop with its own
migration that the plugin can neither correct nor follow up. A published migration that is edited
never runs again on the shops that already executed it.

## Decision

The schema ships as migrations of the plugin, registered by the plugin itself and written against
the DBAL `Schema` API, with every index named as in the mapping and every foreign key given an
explicit name, and ids generated the way the ORM generates them on each platform. A released
migration is never edited: a change is a new migration. A shop is never asked to generate one.

## Consequences

- One migration source for every supported database, and a schema identical in every shop.
- A shop gets a schema change with its usual migrate step after an upgrade.
- The tables are public contract: a breaking schema change is announced in the upgrade guide.

## Related

- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
