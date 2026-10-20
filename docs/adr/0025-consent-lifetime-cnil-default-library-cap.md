# 0025. Consent lifetime: the CNIL default, capped by the library

- Status: accepted
- Date: 2026-10-20

## Context

tarteaucitron.js keeps the visitor's choice for its own default lifetime unless the page sets
`tarteaucitronForceExpire`, a global variable rather than an `init()` option. The CNIL recommends
asking again sooner than that. The library silently ignores a forced lifetime at or above its own
maximum and falls back to its default, so a longer value typed in the back office would look saved
and have no effect.

## Decision

The consent lifetime is a per-channel setting in days, stored in its own column next to the init
options, and written into `tarteaucitronForceExpire` on the shop. It defaults to the duration the
CNIL recommends. The form refuses any value from the library's limit up, and the shop clamps a
stored value into the accepted range again before writing it. A value above the CNIL duration is
accepted, with a CNIL warning.

## Consequences

- A new channel follows the CNIL recommendation without any setting.
- The value the admin saves is the value the library applies.
- A shop cannot keep the choice for the library's maximum or longer, even on purpose.
- The default and the cap are fixed in the plugin: a change in the library's limit or in the CNIL
  recommendation needs a new version of the plugin.

## Related

- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0017. Links, icon and texts are validated on write and again on read](0017-links-icon-texts-validated-on-write-and-read.md)
- [0024. Back-office notices guide, never block](0024-back-office-notices-guide-never-block.md)
