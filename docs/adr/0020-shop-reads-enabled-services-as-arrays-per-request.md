# 0020. The shop reads the enabled services as arrays, once per request

- Status: accepted
- Date: 2026-10-20

## Context

Every shop page needs the consent configuration of its channel. Loading it as entities hydrates a
row for every known service, while a shop uses only a few, and keeps them all in the unit of work:
Doctrine then computes their change sets again at every flush of the request, cart and checkout
included. Filtering the collection of a managed entity instead would leave a partial collection
that a later flush could misread. An application cache would need an invalidation per channel for
one indexed query.

## Decision

The shop has its own read: plain arrays, joined only to the enabled services, so no entity of the
plugin reaches the unit of work. The back office, which edits the configuration, keeps loading the
entity with all its services. The result is kept for the rest of the request, per channel, and
dropped when the kernel resets services between requests. There is no cache across requests.

## Consequences

- A shop page costs one query and no entity, whatever the number of services known to the plugin.
- A disabled service never leaves the database on the shop side.
- Long-running workers do not serve the configuration of a previous request.
- A back-office save shows on the next shop request, with nothing to invalidate.
- The shop and the back office read the same tables two different ways: a new column has to be
  added to both.

## Related

- [0009. A service with an empty required parameter is not emitted](0009-service-with-empty-required-parameter-not-emitted.md)
- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0017. Links, icon and texts are validated on write and again on read](0017-links-icon-texts-validated-on-write-and-read.md)
