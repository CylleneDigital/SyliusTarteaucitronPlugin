# 0030. A domain layer without Sylius dependency

- Status: accepted
- Date: 2026-10-20

## Context

Most of what the plugin knows is about tarteaucitron.js, not about Sylius: the
`tarteaucitron.init()` options and how they map to the library's keys, the services and their
parameters, the CNIL guidance, the library limits, the encoding of values written into JavaScript.
Mixed with the Sylius wiring and the back-office presentation, these rules would be read, changed
and checked through a channel context, a database and a form.

## Decision

The consent rules live in a domain layer that imports nothing from Sylius and renders nothing. It
works on plain values, and its only link to persistence is a constant of the plugin's own
configuration entity. A Sylius layer resolves the channel and the locale, loads and saves the
configuration, and hands the domain plain values; the back-office runtime turns the domain's results
into labels, badges and CSS classes.

## Consequences

- The public tracker API belongs to the domain: a custom tracker is written without any Sylius
  class.
- A change of Sylius version touches the Sylius layer, not the consent rules.
- Rendering choices (Bootstrap classes, tab state) stay out of the domain; it returns plain data -
  the tab an option belongs to, translation keys, a notice level - and the runtime renders it.
- The domain still uses a few Symfony components where they are the plain tool (file finding, a
  service locator contract): it is free of Sylius, not of every framework.

## Related

- [0008. One tracker per job key, duplicates refused](0008-one-tracker-per-job-key-duplicates-refused.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
