# 0010. Plugin-owned tables, one configuration per channel

- Status: accepted
- Date: 2026-10-20

## Context

A Sylius shop can sell through several channels, each with its own host name, locales and
marketing tags: the consent banner and the services it gates differ from one channel to another.
Storing the settings on the Sylius channel would force every shop to extend its channel model.
Installing the plugin must not change what an existing shop loads before someone has configured it.

## Decision

The plugin keeps its data in tables of its own, linked to the Sylius channel: one configuration per
channel, unique on the channel and deleted with it, and one row per service of that configuration,
unique on its job key. The init options, the per-locale links and texts and the parameters of a
service are stored as JSON objects; the consent lifetime, which is not an init option, has a column
of its own. Nothing reaches the shop until an administrator enables the configuration of the
channel: a configuration starts disabled, its services start switched off, and a channel without a
configuration emits nothing.

## Consequences

- The plugin touches no Sylius model and coexists with the shop's own channel customizations.
- Installing or upgrading the plugin never shows a banner or loads a service on its own.
- Each channel has to be configured and saved before the banner shows on it.

## Related

- [0011. Init options are a JSON map rewritten whole on save](0011-init-options-json-map-rewritten-whole-on-save.md)
- [0012. Service rows are seeded and never removed](0012-service-rows-seeded-never-removed.md)
- [0014. A schema change is a new migration, written against the DBAL Schema API](0014-schema-change-is-a-new-migration.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
