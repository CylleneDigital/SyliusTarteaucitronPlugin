# 0013. Choices are stored as strings

- Status: accepted
- Date: 2026-10-20

## Context

Some init options are a choice between a few values, and the values the library expects are not
all of one type: `serviceDefaultState` takes `true`, `false` or `'wait'`. The library compares that
value strictly, so a value of the wrong type is ignored without an error: a service accepted by
default would only load on the next page.

## Decision

Every choice is stored as the string the back office submits, and only one of the listed strings is
kept on read. The conversion happens when the `tarteaucitron.init()` options are built: a choice
whose value is `'true'` or `'false'` is emitted as a boolean, any other value as the string itself.

## Consequences

- Stored choices have one type, whatever the library expects, and a wrong expectation is fixed in
  the conversion without touching the stored data.
- A new choice whose library values are booleans has to use `'true'` / `'false'`, or the library
  silently ignores it.
- A choice the library expects as the literal string `'true'` or `'false'` cannot be expressed.

## Related

- [0004. Upstream behaviour is followed, not modelled](0004-upstream-behaviour-followed-not-modelled.md)
- [0010. Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md)
