# 0001. tarteaucitron.js is vendored and self-hosted, never loaded from a CDN

- Status: accepted
- Date: 2026-10-20

## Context

The consent banner runs on every shop page, before the visitor has agreed to anything. Loaded from a
CDN or from its publisher, the library itself would be a request to a third party made before any
consent, and the shop would depend on that host for its availability and for the version it serves.
The library also resolves its stylesheet, language, services and ad-blocker files from the URL of
its own script, so those files have to be served next to it. Its publisher sells a commercial
offering on top of the free library.

## Decision

The plugin performs the free, self-hosted install. It ships the free open-source library inside the
package, with its whole file set, and the shop serves it from its own public directory once the
bundle assets are installed. The plugin never points the library at another host and never turns on
the commercial features, so the library sends no visitor data to its publisher.

## Consequences

- Out of the box, showing the banner needs no request to any host but the shop; only an icon the
  admin points at another host adds one.
- The library version is the plugin's: a shop gets a newer library by upgrading the plugin, then
  installs the bundle assets again.
- The library files must stay together: bundling the library into a theme build, or serving only
  part of its files, breaks the files it loads itself.
- The commercial features (hosted consent records, for instance) are not available through the
  plugin.
- The library's licence notice ships with it and must be kept.

## Related

- [0002. The vendored library is never patched](0002-vendored-library-never-patched.md)
- [0005. The shop snippet follows the official install](0005-shop-snippet-follows-the-official-install.md)
- [0037. The scope stops at the free library's Sylius wiring](0037-scope-limited-to-the-free-library-wiring.md)
