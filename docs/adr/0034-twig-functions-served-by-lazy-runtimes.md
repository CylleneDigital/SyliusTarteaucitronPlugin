# 0034. Twig functions are served by lazy runtimes

- Status: accepted
- Date: 2026-10-20

## Context

Twig loads every extension whenever it renders a template. The shop functions need the current
channel's consent configuration, the tracker registry, the locale and the CSP nonce; the back-office
functions need the option catalog and the CNIL and library checks. Held by the extension, all of
this would be built on every page that renders a template, including the many pages where neither
set of functions is called.

## Decision

One Twig extension, without dependencies, declares every function and filter; each is served by a
runtime, built on the first call of one of its functions. The shop and the back office have a
runtime each.

## Consequences

- A shop page only builds the shop dependencies; the back-office page builds its own, plus the shop
  runtime for the CSP nonce. A page that calls no plugin function builds neither.
- The shop functions and filter are public contract; the extension, the runtimes and the back-office
  functions are internal.

## Related

- [0020. The shop reads the enabled services as arrays, once per request](0020-shop-reads-enabled-services-as-arrays-per-request.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
- [0032. Services are declared explicitly, without autowiring](0032-explicit-services-no-autowiring.md)
