# 0016. Every value written into JavaScript goes through InlineJson

- Status: accepted
- Date: 2026-10-20

## Context

The shop snippet writes back-office values into inline scripts: the init options, the custom texts,
the forced language and expiry, the identifiers of each service. A value holding `</script>`, a
quote or an HTML comment opener would break out of the script, and anyone able to write the
configuration could inject code into every shop page. Twig's `json_encode` is not tuned for inline
script or attribute output, and escaping each call site by hand would miss one sooner or later.

## Decision

A single encoder, `InlineJson`, writes every value that ends up in JavaScript: it escapes angle
brackets, ampersands and both quotes, and fails loudly on a value it cannot encode. Templates use it
through the public `|tarteaucitron_json` filter, never `json_encode`; `|raw` is only applied to its
output, or to the service snippets it built. The `tarteaucitron.user` keys those snippets write are
not values but identifiers, refused unless they are plain JavaScript identifiers.

## Consequences

- The escaping is defined once, and the output stays inert both in a script and in an attribute.
- A theme overriding the shop template has to keep the filter on every value it adds.
- A value that is not valid UTF-8 cannot reach the encoder: the JSON columns refuse it on write and
  on read, so the failure mode never shows on a shop page.

## Related

- [0005. The shop snippet follows the official install](0005-shop-snippet-follows-the-official-install.md)
- [0017. Links, icon and texts are validated on write and again on read](0017-links-icon-texts-validated-on-write-and-read.md)
- [0018. The CSP nonce comes from a provider, fixed or per response](0018-csp-nonce-from-a-provider.md)
