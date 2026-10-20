# 0003. Every upstream init option and built-in service key is exposed or listed as a gap

- Status: accepted
- Date: 2026-10-20

## Context

A library upgrade can add an option to `tarteaucitron.init()`, or a new `tarteaucitron.user.*` key
to a service the plugin offers. Unnoticed, the new option stays out of reach of the shops; exposed
in a hurry, it lands somewhere nobody chose.

## Decision

The vendored library is the reference. Every option it reads from its init parameters is either
exposed - in the back office or in the theme-level configuration - or listed as a deliberate gap.
Every user key read by a built-in service is either a parameter of that service or listed as a gap
for it. The plugin exposes no option the library does not read. The comparison is made against the
vendored files themselves, so an upgrade that adds an option or a key does not go through until it
has been placed or listed.

## Consequences

- Shops get the library's whole option set, not a curated subset.
- Every library upgrade carries an explicit decision for what it adds.
- The listed gaps are the service keys a back-office text field cannot carry: callbacks, lists,
  booleans, and the library's own state.
- Services the plugin does not ship are not covered by this rule; a service declared in
  configuration is checked against the same files instead.

## Related

- [0002. The vendored library is never patched](0002-vendored-library-never-patched.md)
- [0006. Per-channel options in the back office, theme-level options in configuration](0006-channel-options-in-back-office-theme-options-in-configuration.md)
- [0007. A Sylius-oriented service selection, others added by configuration or by a tagged class](0007-sylius-oriented-service-selection-others-added-by-the-application.md)
