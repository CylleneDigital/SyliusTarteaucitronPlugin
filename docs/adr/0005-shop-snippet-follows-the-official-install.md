# 0005. The shop snippet follows the official install

- Status: accepted
- Date: 2026-10-20

## Context

The library's publisher documents one install for the free library: the global variables it reads,
then the library script in `<head>`, then `tarteaucitron.init()` with the options, then for each
service its `tarteaucitron.user.*` keys and a push to `tarteaucitron.job`. Themes and the upstream
documentation build on that shape: inline calls to `tarteaucitron.*`, placeholders for embedded
content, links that open the preferences panel.

## Decision

The snippet the plugin renders in the shop `<head>` hook is that install, in that order, with the
library loaded synchronously, without `defer` or `async`. What the plugin adds stays around it
without changing the order: low-priority preloads of the files the library will request, the
plugin stylesheet as a separate file and a nonce on each script. Moving the snippet to the end of
the body, deferring the library or inlining the stylesheet are not done by the plugin.

## Consequences

- What the upstream documentation says works on a plain install works on a Sylius shop.
- The library blocks the parsing of the page while it loads. A theme that makes no inline
  `tarteaucitron.*` call can override the template to defer it, after measuring the effect.
- A library failure the plugin can avoid from the snippet is worked around there: the snippet
  creates the job list itself, so that, with ad-blocker detection on and no service enabled, the
  library does not stop before wiring its buttons.
- The plugin stylesheet costs a request: inlining it would need a style nonce the plugin does not
  provide.

## Related

- [0001. tarteaucitron.js is vendored and self-hosted, never loaded from a CDN](0001-library-vendored-and-self-hosted.md)
- [0004. Upstream behaviour is followed, not modelled](0004-upstream-behaviour-followed-not-modelled.md)
- [0016. Every value written into JavaScript goes through InlineJson](0016-javascript-values-encoded-by-inline-json.md)
- [0018. The CSP nonce comes from a provider, fixed or per response](0018-csp-nonce-from-a-provider.md)
- [0022. Asset URLs are fingerprinted, cascaded files are preloaded](0022-asset-urls-fingerprinted-cascaded-files-preloaded.md)
