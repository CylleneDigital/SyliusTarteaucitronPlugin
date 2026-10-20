# 0004. Upstream behaviour is followed, not modelled

- Status: accepted
- Date: 2026-10-20

## Context

The library's services file says, for each service, whether it needs consent, which script it
loads, which cookies it sets and under which category the visitor finds it. Upstream changes these
facts between versions, for example when a service starts waiting for consent. A copy of them in
the plugin would drift from the library it ships and could contradict it.

## Decision

The plugin hands the library what the official install asks of a site - the init options, and for
each enabled service its user keys and its job - and leaves the service behaviour to the library.
It keeps no per-service consent requirement, script or cookie list of its own. What it knows about a
service - its group in the back office, its consent-mode badge, an admin hint - only serves the back
office and never changes what the library does on the shop. A library behaviour that limits an
option is shown to the admin and documented, not overridden. When an upgrade changes what a visitor
sees, the upgrade notes tell shops.

## Consequences

- A library upgrade can change the shop's behaviour without any plugin code change.
- The behaviour of a service is the one the upstream documentation describes for the vendored
  version.
- The back office offers no per-service override of the consent requirement.
- The back-office hints describe the services the plugin knows; they can lag behind upstream
  without affecting visitors.

## Related

- [0002. The vendored library is never patched](0002-vendored-library-never-patched.md)
- [0005. The shop snippet follows the official install](0005-shop-snippet-follows-the-official-install.md)
- [0024. Back-office notices guide, never block](0024-back-office-notices-guide-never-block.md)
