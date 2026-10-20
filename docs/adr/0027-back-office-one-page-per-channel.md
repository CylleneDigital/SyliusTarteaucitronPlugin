# 0027. The back office is one page per channel, not a Sylius resource

- Status: accepted
- Date: 2026-10-20

## Context

A channel has at most one consent configuration, and a configuration means nothing without its
channel. A Sylius resource would bring a grid, create and delete actions and a show page that have
no use here. In the admin, the Sylius channel context answers for the shop, not for the channel the
admin wants to edit.

## Decision

The back office is a single page, served by one invokable action, which edits the configuration of
the channel named in the `channelCode` query parameter, or of the first channel when none is given.
The channel is resolved from that parameter, never from the shop channel context. The plugin
entities are not Sylius resources: the page reuses the Sylius admin layout and its update hooks,
with the actions it has no use for disabled. On the first visit the configuration is built in
memory, with the defaults and every seeded service switched off, and it is only written when the
admin saves.

## Consequences

- There is no list to manage: an admin picks a channel and edits it.
- Visiting the page writes nothing; a channel never saved has no configuration, and nothing is
  emitted on its shop.
- The page does not get the Sylius resource features (generic grid, resource events, API).
- The channel switcher and the redirect after a save carry the channel code; the menu entry does
  not, and opens the first channel.

## Related

- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
- [0012. Service rows are seeded and never removed](0012-service-rows-seeded-never-removed.md)
- [0015. The last save wins](0015-last-save-wins.md)
- [0028. The back office is organised by intention](0028-back-office-organised-by-intention.md)
