# 0024. Back-office notices guide, never block

- Status: accepted
- Date: 2026-10-20

## Context

A shop admin can configure the banner in ways that depart from the CNIL cookie guidance: implied
consent, no way to refuse as easily as to accept, services loaded before any choice, a choice kept
too long. The plugin cannot decide what is lawful for a given shop, and should not invent rules of
its own. Other settings are not a legal matter but still surprise the admin: options the library
silently ignores in some combinations, or a consent mode switched on without any of the services it
applies to.

## Decision

The back office shows three families of notices about the options, kept apart and never blocking a
save; the services left out of the shop for an empty required parameter have their own warning:

- CNIL guidance: a summary above the tabs, a count on each tab concerned and a warning under each
  field. Only rules that follow the guidance directly are flagged; a setting whose acceptability is
  a legal call per shop, such as soft consent mode loading tags in restricted mode before consent,
  is documented and deliberately not flagged.
- Library limits: an option combination the library ignores, reported as information.
- Consent-mode hints: a Google or Bing consent mode switched on while none of its services is
  enabled, or Google consent mode with only a tag manager, shown next to the option.

The admin keeps the last word, and compliance stays the merchant's responsibility.

## Consequences

- An admin sees why a setting is risky or ineffective without being prevented from choosing it.
- The default configuration raises no CNIL warning.
- A new rule goes in the family it belongs to: a CNIL rule needs a clear basis in the guidance, a
  library quirk goes with the library limits.

## Related

- [0004. Upstream behaviour is followed, not modelled](0004-upstream-behaviour-followed-not-modelled.md)
- [0009. A service with an empty required parameter is not emitted](0009-service-with-empty-required-parameter-not-emitted.md)
- [0025. Consent lifetime: the CNIL default, capped by the library](0025-consent-lifetime-cnil-default-library-cap.md)
- [0028. The back office is organised by intention](0028-back-office-organised-by-intention.md)
