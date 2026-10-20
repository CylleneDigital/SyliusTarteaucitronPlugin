# 0026. Per-locale links and texts are limited to the banner essentials

- Status: accepted
- Date: 2026-10-20

## Context

A single privacy policy link per channel is wrong for a shop that sells in several languages, and
the banner texts could not be changed at all. The library has a long list of texts, through
`tarteaucitronCustomText`; exposing all of them would bring back a screen too long to read, for
texts a shop rarely needs to change.

## Decision

The back office has one block per locale enabled on the channel. Each block overrides two links of
the channel - the privacy policy and the read-more link, passed to `tarteaucitron.init()` - and a
short, fixed set of texts: the banner title, message and buttons, the introduction of the
preferences panel and the mandatory cookies text. An empty field keeps the channel link or the
library's own text, shown as a placeholder in the language the shop will use. On the shop, only the
values set for the current locale replace the channel links and the library texts.

## Consequences

- A multilingual shop links each language to its own policy and words its own banner.
- The channel links remain the default for every locale left empty.
- The other library texts stay as translated upstream; a shop that needs to change them overrides
  the shop template.
- Values set for a locale later removed from the channel are kept, and come back with the locale.
- Links and texts the library inserts into HTML are checked like any other value written into the
  page.

## Related

- [0012. Service rows are seeded and never removed](0012-service-rows-seeded-never-removed.md)
- [0017. Links, icon and texts are validated on write and again on read](0017-links-icon-texts-validated-on-write-and-read.md)
- [0023. The banner language is forced server-side](0023-banner-language-forced-server-side.md)
- [0028. The back office is organised by intention](0028-back-office-organised-by-intention.md)
