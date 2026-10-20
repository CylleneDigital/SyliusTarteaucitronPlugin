# 0036. The plugin does not cap a dependency for an upstream bug

- Status: accepted
- Date: 2026-10-20

## Context

A bug in Sylius, or between Sylius and one of its dependencies, can break some combinations of
versions the plugin supports, without the plugin being involved: a Sylius mapping, for instance,
rejected by a stricter version of the ORM. Capping the dependency in the plugin's requirements would
impose the cap on every shop, including the ones on a Sylius version the bug does not affect, or
that work around it their own way.

## Decision

The plugin's requirements stay open to every version it supports: it does not cap a dependency for a
bug that is not its own.

## Consequences

- The plugin never holds a shop back within the major versions it supports, for a bug that is not
  its own.
- A shop affected by the upstream bug applies the workaround itself, in its own requirements.

## Related

- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
