# 0037. The scope stops at the free library's Sylius wiring

- Status: accepted
- Date: 2026-10-20

## Context

A consent plugin is easily asked for more than the library does: gating scripts the theme hard-codes
or a tag manager loads, keeping a proof of each visitor's consent on the server, carrying a shop's
own consent policy, styling the banner to a theme, or using a commercial edition. Each of these
depends on one shop, its theme or its legal choices, and would make the plugin something other than
an install of the free tarteaucitron.js.

## Decision

The plugin wires the free tarteaucitron.js into Sylius and stops there. It gates the services
enabled in its back office - built in, declared in its configuration or added by a tagged class -
and nothing else. It keeps no server-side record of consent: the visitor's choice lives in the
library's cookie. Its CNIL warnings are guidance, not legal advice. Shop-specific policies, theme
styling and hard-coded third-party scripts stay in the project that uses the plugin; the plugin only
ships the style fixes the library needs to render correctly on a Sylius shop.

## Consequences

- A script hard-coded in the theme is not under consent until the shop declares it as a service; a
  tag that a tag manager loads outside the plugin is not gated by it at all.
- For embedded content (videos, maps, social posts), the plugin loads the service; the theme writes
  the placeholders the library replaces.
- A shop that needs a proof of consent, or legal compliance beyond the warnings, builds it on its
  side; compliance stays the merchant's responsibility.
- A theme can replace the library's stylesheet through the theme-level options; the plugin then
  stops loading and preloading the library's own.

## Related

- [0001. tarteaucitron.js is vendored and self-hosted, never loaded from a CDN](0001-library-vendored-and-self-hosted.md)
- [0007. A Sylius-oriented service selection, others added by configuration or by a tagged class](0007-sylius-oriented-service-selection-others-added-by-the-application.md)
- [0024. Back-office notices guide, never block](0024-back-office-notices-guide-never-block.md)
