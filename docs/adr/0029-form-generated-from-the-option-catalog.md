# 0029. The form is generated from the option catalog and mapped by data mappers

- Status: accepted
- Date: 2026-10-20

## Context

The configuration does not map onto entity properties field by field: the init options live in one
stored bag, with defaults and value normalisation, the per-locale links and texts in another, the
locales depend on the channel and the service fields on each tracker's parameters. Writing a field
per option by hand would duplicate the option catalog. Giving the generated fields their value while
something else also maps them wrote the same data from two places, and a choice field left without a
value showed an empty entry that silently saved the default.

## Decision

The form is generated when it receives its configuration: one field per option of the catalog, typed
from the option, and one block per locale of the channel. Data mappers read and write every field,
in both directions, and are their only source of data: the generated fields carry no data and no
mapping option, since Symfony maps the data right after the fields are generated. Choice fields have
no empty entry.

## Consequences

- A new init option appears in the back office from its catalog entry, without a form change.
- The normalisation of stored values happens in one place, on the way in and on the way out.
- A field that needs custom wiring is wired in the data mapper, not in its definition.
- Values of a removed locale and the row of a removed tracker, which the form does not show, are
  left untouched by a save, since the mapper decides what it writes; the init options map, on the
  other hand, is rewritten whole.

## Related

- [0012. Service rows are seeded and never removed](0012-service-rows-seeded-never-removed.md)
- [0013. Choices are stored as strings](0013-choices-stored-as-strings.md)
- [0028. The back office is organised by intention](0028-back-office-organised-by-intention.md)
