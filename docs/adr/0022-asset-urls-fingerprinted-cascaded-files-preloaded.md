# 0022. Asset URLs are fingerprinted, cascaded files are preloaded

- Status: accepted
- Date: 2026-10-20

## Context

Installing the assets copies the files without versioning them, so browsers kept a stale library
or stylesheet after a plugin update. The library also loads further files on its own once the page
has loaded - its language file, its services file and its stylesheet - which costs extra round
trips. It strips the query string from its own URL to find them and versions them with its own
version; their URLs cannot be changed without patching it.

## Decision

The URLs the plugin prints itself - the library, the plugin stylesheet, the back-office stylesheet
and script - carry a content fingerprint, appended after any version the application already adds.
The shop template preloads the three files the library loads in cascade, at the exact URLs the
library builds, with a low fetch priority and the CSP nonce on the scripts. A preload is left out
when the theme ships that file itself. The asset version functions refuse a file that is not part
of the plugin's assets.

## Consequences

- A plugin update reaches browsers without a hard reload, as long as the assets are installed again.
- The cascaded files arrive without delaying the page's own resources, and are not fetched twice.
- If the library changes the URLs it builds, the preloads must follow, or browsers download those
  files twice.
- `tarteaucitron_library_version()` exists for these preloads and is part of the public Twig API.

## Related

- [0002. The vendored library is never patched](0002-vendored-library-never-patched.md)
- [0005. The shop snippet follows the official install](0005-shop-snippet-follows-the-official-install.md)
- [0018. The CSP nonce comes from a provider, fixed or per response](0018-csp-nonce-from-a-provider.md)
- [0021. Library facts are computed when the container is compiled](0021-library-facts-computed-at-compile-time.md)
