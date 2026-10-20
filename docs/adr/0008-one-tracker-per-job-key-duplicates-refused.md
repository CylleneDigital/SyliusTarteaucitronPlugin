# 0008. One tracker per job key, duplicates refused

- Status: accepted
- Date: 2026-10-20

## Context

Each service needs a definition: its job key, its group, the parameters that fill its user keys,
whether it is an embed, its consent mode and admin hint. The job key is the service's identity: the
stored rows and the library both use it, so two definitions on one key would silently shadow each
other. A shop page renders only the few services enabled on its channel, so building every
definition on every request is wasted work.

## Decision

A job key has exactly one tracker, registered as a tagged container service: one class per
built-in service, a shop's own class implementing the same contract, or a generic definition for an
entry declared in configuration. When the container is compiled, every definition whose job key can
be read without running the application goes into a locator keyed by job key, and a duplicate key
fails the build. A definition whose key is only known once built - constructor arguments, a
factory, method calls - is kept in a list read on first need, where a duplicate fails then.

## Consequences

- Adding a built-in service is adding one class; nothing else registers it.
- The shop only builds the definitions of the services it renders.
- A duplicate among definitions with arguments is only caught at runtime.
- A definition registered without the tag never reaches the registry.

## Related

- [0007. A Sylius-oriented service selection, others added by configuration or by a tagged class](0007-sylius-oriented-service-selection-others-added-by-the-application.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
- [0032. Services are declared explicitly, without autowiring](0032-explicit-services-no-autowiring.md)
