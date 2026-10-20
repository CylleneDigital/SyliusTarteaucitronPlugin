# 0011. Init options are a JSON map rewritten whole on save

- Status: accepted
- Date: 2026-10-20

## Context

The `tarteaucitron.init()` options change with the library, and some of its JavaScript keys are
irregular. A column per option would turn each library upgrade into a schema change, and storing
the library's own keys would tie the stored data to its spelling.

## Decision

The init options of a configuration are one JSON map under the plugin's own snake_case keys,
turned into the library's keys when the shop snippet is built. On read, a missing key takes the
default of the option catalog and an unknown key is ignored. A new configuration stores every
default, and each save rewrites the whole map with a value for every option the plugin knows.

## Consequences

- An option added by a library upgrade needs no schema change: until the next save it reads as its
  default.
- A key the plugin no longer knows is dropped on the next save.
- A default changed in a later version never reaches a configuration already saved: an upgrade that
  needs it changed says so in the upgrade guide.
- The snake_case keys are a contract: renaming one is a data change, not a refactoring.

## Related

- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0013. Choices are stored as strings](0013-choices-stored-as-strings.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
