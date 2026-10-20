# 0023. The banner language is forced server-side

- Status: accepted
- Date: 2026-10-20

## Context

Left alone, tarteaucitron.js picks its language from the first two letters of `<html lang>`, then
from the browser. Sylius locale codes do not always match the library's codes (Norwegian Bokmål is
`nb` for Sylius, `no` for the library), so such a shop would get the visitor's browser language
instead of its own. The per-locale texts set in the back office and the preload of the language
file also need to know, on the server, which language the banner will use.

## Decision

The plugin resolves the language from the Sylius locale context and passes it to the library as
`tarteaucitronForceLanguage`. The full locale is tried first, then its primary subtag, both against
the library's own list of languages, with English as the fallback. A `locale_aliases` bundle key
maps the locale codes that differ from the library's; the plugin ships the Norwegian one by default.
The back office uses the same mapping to show the library's default texts for each locale.

## Consequences

- The banner follows the shop's locale, not the browser's.
- The language file can be preloaded, and the texts set for a locale land on the banner in that
  locale.
- A locale the library does not translate shows the banner in English.
- Setting `locale_aliases` replaces the default map: a shop that adds its own aliases has to keep
  the Norwegian one if it needs it.

## Related

- [0021. Library facts are computed when the container is compiled](0021-library-facts-computed-at-compile-time.md)
- [0022. Asset URLs are fingerprinted, cascaded files are preloaded](0022-asset-urls-fingerprinted-cascaded-files-preloaded.md)
- [0026. Per-locale links and texts are limited to the banner essentials](0026-per-locale-links-and-texts-limited-to-banner-essentials.md)
