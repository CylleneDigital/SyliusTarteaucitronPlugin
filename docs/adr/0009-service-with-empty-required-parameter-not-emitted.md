# 0009. A service with an empty required parameter is not emitted

- Status: accepted
- Date: 2026-10-20

## Context

A service can be switched on in the back office while its identifier, such as a measurement or pixel
id, is still empty. Loading it on the shop without that identifier collects nothing, yet the visitor
would still be asked to consent to it. A warning next to the service alone is easy to miss in a long
services column, among several categories.

## Decision

The shop emits a service only when every required parameter is filled; a value made of spaces
counts as empty. Otherwise it writes neither its user keys nor its job, so the service is absent from
the visitor's panel. Saving is not refused. The back office lists the enabled services that are not
loaded on the shop at the top of the services column, and warns next to each of them.

## Consequences

- The shop never loads a half-configured service, and the visitor is never asked about one.
- The switch in the back office and what the shop loads can differ; the summary is where the admin
  sees it.
- An optional parameter, such as a custom domain, never holds a service back.

## Related

- [0005. The shop snippet follows the official install](0005-shop-snippet-follows-the-official-install.md)
- [0024. Back-office notices guide, never block](0024-back-office-notices-guide-never-block.md)
