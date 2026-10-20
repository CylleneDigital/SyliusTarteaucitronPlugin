# 0002. The vendored library is never patched

- Status: accepted
- Date: 2026-10-20

## Context

The library is maintained upstream and released often. A local edit to the vendored files would be
lost at the next upgrade unless someone remembered to carry it over, would make the copy differ from
any published tag, and would turn every upgrade into a manual merge. The library still has bugs and
limits a Sylius shop runs into.

## Decision

The vendored files are never edited. An upgrade replaces the whole upstream file set from a release
tag, optionally checked against an archive checksum, and records the version and the source it came
from. The plugin's own stylesheet adjustment is a separate file next to the library, kept across
upgrades. A library bug or limit is worked around outside the library, or, when that is not
possible, shown to the admin next to the option concerned and documented.

## Consequences

- The vendored copy can be compared with an upstream tag at any time.
- An upgrade is mechanical; what remains is deciding what to do with what the new version adds.
- Some improvements are out of reach: the files the library loads itself can only be versioned by
  the library version, not by their content, and the limits of some options remain.
- Display fixes live in the plugin's own stylesheet beside the library, and snippet-level
  workarounds in the shop snippet.
- The plugin stylesheet targets the library's element ids and classes, so it has to be reviewed
  when the library changes its markup.

## Related

- [0001. tarteaucitron.js is vendored and self-hosted, never loaded from a CDN](0001-library-vendored-and-self-hosted.md)
- [0003. Every upstream init option and built-in service key is exposed or listed as a gap](0003-every-upstream-option-exposed-or-listed-as-a-gap.md)
- [0004. Upstream behaviour is followed, not modelled](0004-upstream-behaviour-followed-not-modelled.md)
- [0022. Asset URLs are fingerprinted, cascaded files are preloaded](0022-asset-urls-fingerprinted-cascaded-files-preloaded.md)
