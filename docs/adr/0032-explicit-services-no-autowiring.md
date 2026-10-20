# 0032. Services are declared explicitly, without autowiring

- Status: accepted
- Date: 2026-10-20

## Context

Symfony recommends that a reusable bundle relies neither on autowiring nor on autoconfiguration: its
services must not depend on what the host application happens to alias or tag. An alias by class or
interface would also let the application autowire classes that are internal, and make them look like
an extension point.

## Decision

Every service is declared by hand, private, with every argument and tag written out: in PHP
configurators, or in the extension for those that depend on the bundle configuration (the nonce
provider, the trackers declared in configuration). The one exception is the built-in trackers: a
prototype registers every tracker class and tags it, without autowiring, since none of them takes an
argument. Ids are prefixed with the plugin's alias, except where a service is looked up by its
class: the controller, which its route names by class, the repository, which Doctrine finds by
class, and the built-in trackers, whose ids the prototype derives from their classes. No class or
interface is aliased for autowiring. The other way round, the plugin registers autoconfiguration for
the tracker interface, so an application's own tracker is tagged without configuration.

## Consequences

- A new constructor argument, or a tag autoconfiguration would have set (reset, Twig runtime, event
  listener), is added to the definition by hand.
- An application wanting an internal service references its prefixed id, knowing it may change in a
  minor version.
- A built-in tracker keeps an empty constructor, or it would need a definition of its own.
- The services whose id is their class - the controller, the repository, the built-in trackers - can
  still be autowired by that class; they stay internal all the same.

## Related

- [0008. One tracker per job key, duplicates refused](0008-one-tracker-per-job-key-duplicates-refused.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
- [0033. A `Bundle` class with `SyliusPluginTrait`, not `AbstractBundle`](0033-bundle-class-with-sylius-plugin-trait.md)
