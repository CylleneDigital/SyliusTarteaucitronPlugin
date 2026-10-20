# 0017. Links, icon and texts are validated on write and again on read

- Status: accepted
- Date: 2026-10-20

## Context

Encoding protects the scripts, not what the library does with the values afterwards: tarteaucitron.js
concatenates the links and the icon source into double-quoted attributes and navigates to the privacy
link, and inserts the banner texts into its HTML. A `javascript:` link, an SVG data image or a quote
in a text would run or break out once the library builds the banner. The configuration can also be
written outside the back office, by an import, a script or fixtures.

## Decision

The back office refuses unsafe values: links must be HTTP(S) URLs or paths relative to the shop,
without quotes, angle brackets, whitespace or control characters; the icon also accepts a raster
`data:` image, never SVG; banner texts refuse angle brackets and double quotes and are length
capped; service parameters are length capped too; the cookie name and domain must match their
syntax. The same rules, except the length cap on service parameters, run again when the
configuration is read, for the shop and the back office
alike: an unsafe link is dropped, an invalid cookie setting falls back to its default, unsafe texts
and non-string values are left out, and on the shop the consent lifetime is clamped.

## Consequences

- An unsafe link, icon, text or cookie setting written around the back office cannot reach the
  shop page; a service parameter written there still goes through the JavaScript encoding.
- An invalid stored value disappears silently on the shop; the back office shows the cleaned value
  and the next save makes it final.
- The write and read rules have to stay in step.

## Related

- [0016. Every value written into JavaScript goes through InlineJson](0016-javascript-values-encoded-by-inline-json.md)
- [0025. Consent lifetime: the CNIL default, capped by the library](0025-consent-lifetime-cnil-default-library-cap.md)
- [0026. Per-locale links and texts are limited to the banner essentials](0026-per-locale-links-and-texts-limited-to-banner-essentials.md)
