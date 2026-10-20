# 0012. Service rows are seeded and never removed

- Status: accepted
- Date: 2026-10-20

## Context

The services a shop can switch on come from the code: the built-in trackers and those declared in
the bundle configuration. That list changes when the plugin is upgraded or the configuration is
edited, while the identifiers a merchant typed for a service live in the database. Deleting a row
when its tracker disappears would lose those identifiers for good, for instance after a configuration
key removed by mistake.

## Decision

Opening the back-office page of a channel, not installing the plugin, adds the missing rows,
switched off and with empty parameters, for every service the code knows; they are written with
the next save. That is the only place where service
rows are created: the form cannot add one. No row is ever removed: the row of a service the code no
longer knows stays untouched, hidden in the back office and skipped by the shop. Texts set for a
locale since removed from the channel are kept the same way. Init option keys are not covered:
the map is rewritten on each save.

## Consequences

- A new service appears in the back office of every channel, switched off, without a migration.
- A service that comes back finds its identifiers and its state as they were.
- The tables can hold rows nothing reads; removing them is left to the shop.
- A tracker that opts out of seeding can never be switched on.

## Related

- [0008. One tracker per job key, duplicates refused](0008-one-tracker-per-job-key-duplicates-refused.md)
- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0015. The last save wins](0015-last-save-wins.md)
