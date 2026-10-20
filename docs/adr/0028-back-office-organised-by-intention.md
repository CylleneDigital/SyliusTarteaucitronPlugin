# 0028. The back office is organised by intention

- Status: accepted
- Date: 2026-10-20

## Context

The library exposes many init options that mix business, legal, appearance and expert concerns.
Listed on one page, mostly without explanation, they made the screen intimidating for a shop
admin. The channel being configured was not visible when the shop had a single channel, and the
services column grew with every supported service.

## Decision

The options are grouped in tabs by what the admin wants to do: essentials, compliance, texts and
languages, display, advanced. The grouping is presentation only: it is never stored, so an option
can move to another tab without a data change. Every option has a label and a help text in each
shipped language, derived from its key. The current channel is always shown, with a switcher when
there are several. An invalid tab opens first, and the active tab survives a save. The services
column shows one line per service, its details folded until it is switched on, a count of enabled
services per category and a search.

## Consequences

- An admin finds a setting by intention rather than by library option name.
- Adding an option means choosing its tab and writing its help text, in every shipped language.
- The screen stays usable as the list of supported services grows.
- The tab memory and the services column behaviour need a script, kept out of the page itself.

## Related

- [0006. Per-channel options in the back office, theme-level options in configuration](0006-channel-options-in-back-office-theme-options-in-configuration.md)
- [0019. No inline script in the back office](0019-no-inline-script-in-back-office.md)
- [0024. Back-office notices guide, never block](0024-back-office-notices-guide-never-block.md)
- [0026. Per-locale links and texts are limited to the banner essentials](0026-per-locale-links-and-texts-limited-to-banner-essentials.md)
