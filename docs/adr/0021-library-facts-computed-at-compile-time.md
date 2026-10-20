# 0021. Library facts are computed when the container is compiled

- Status: accepted
- Date: 2026-10-20

## Context

Every shop page needs a few facts about the vendored library: its version, the languages it ships,
and a fingerprint of the files the plugin links to. Reading the library files and hashing them on
each request costs time for values that only change when the plugin is updated.

## Decision

The facts the shop needs on every page are read once, when the container is compiled, and stored
as container parameters: the library version, its list of languages, and a content fingerprint of
every vendored file and of the back-office assets. The library and back-office asset directories
are registered as container resources, so a debug container is rebuilt when they change.

Reads that only the back office needs, such as the library's default texts for a language, stay on
demand at request time: they are rare, and limited to the language asked for.

## Consequences

- A shop request reads no library file and hashes nothing.
- A production container keeps the old facts after a library upgrade until its cache is cleared,
  as after any update of the code it runs.
- The same compile step is where a declared service unknown to the vendored library fails, before
  any request.

## Related

- [0001. tarteaucitron.js is vendored and self-hosted, never loaded from a CDN](0001-library-vendored-and-self-hosted.md)
- [0022. Asset URLs are fingerprinted, cascaded files are preloaded](0022-asset-urls-fingerprinted-cascaded-files-preloaded.md)
- [0023. The banner language is forced server-side](0023-banner-language-forced-server-side.md)
