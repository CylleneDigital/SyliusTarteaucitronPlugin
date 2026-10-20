# 0031. A small public API: stored keys are the contract

- Status: accepted
- Date: 2026-10-20

## Context

The plugin keeps its configuration in the shop's database, and shop themes call its Twig functions
or override its template. A break there - a renamed option key, a moved hook, a changed function -
does not fail loudly: it can hide the banner, or change what loads before consent. Every class or
service declared public, on the other hand, freezes the code behind it.

## Decision

The public contract is kept small and lists what shops store or write: the option keys, the
per-locale keys and the service job keys saved in the database, the plugin's tables, the bundle
configuration keys, the shop Twig functions and filter, the Twig hooks and the admin route, the
translation keys a custom tracker relies on, the tracker tag, and the few PHP types needed to add a
tracker or a nonce provider. Everything else is internal and says so, including the built-in tracker
classes: for a built-in service, only its job key counts.

## Consequences

- The contract only breaks in a major version, with the steps written in the upgrade guide
  beforehand.
- Internal code, service ids and built-in tracker classes change freely in a minor version;
  decorating an internal service works but is not covered.
- A method may be added to the tracker interface in a minor version, always with a default in the
  abstract tracker class: extending that class is the supported way to write a tracker.

## Related

- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0013. Choices are stored as strings](0013-choices-stored-as-strings.md)
- [0034. Twig functions are served by lazy runtimes](0034-twig-functions-served-by-lazy-runtimes.md)
- [0035. Twig hooks are prepended by the extension](0035-twig-hooks-prepended-by-the-extension.md)
