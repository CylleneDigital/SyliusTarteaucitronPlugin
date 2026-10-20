# 0006. Per-channel options in the back office, theme-level options in configuration

- Status: accepted
- Date: 2026-10-20

## Context

The options of `tarteaucitron.init()` are of two kinds. Some are consent and display choices a shop
admin makes, and some of them legitimately differ per channel, like the privacy policy or the cookie
domain of a channel on its own domain. Others depend on the theme: a stylesheet or scripts the theme
ships instead of the library's, the URL fragment its links use, the id of one of its elements. A
wrong value for those breaks the banner or a theme link, and an admin cannot tell. Once shops store
an option under its key, moving it is a breaking change.

## Decision

Options an admin can set stay in the back office, stored per channel; the technical ones that shops
need per channel, such as the cookie name and domain, sit in its advanced part, checked for format
only.
Options that belong to the theme integration are bundle configuration, under `integration:`, shared
by every channel and versioned with the theme. They are checked when the container is built where
their format matters, and they are applied after the back-office options. A stored value whose key
is not a back-office option is ignored.

## Consequences

- An admin cannot break the theme integration from the back office. The advanced options are only
  checked for format: a cookie domain the shop is not served on still loses every consent.
- A theme-level option takes the same value on every channel, and changing it means a deployment.
- Each new option needs a choice of place, made with the question "does a wrong value break the
  theme integration, and can it differ per channel?".

## Related

- [0003. Every upstream init option and built-in service key is exposed or listed as a gap](0003-every-upstream-option-exposed-or-listed-as-a-gap.md)
- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
