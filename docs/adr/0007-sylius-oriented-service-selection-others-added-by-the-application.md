# 0007. A Sylius-oriented service selection, others added by configuration or by a tagged class

- Status: accepted
- Date: 2026-10-20

## Context

The library knows a few hundred services, most of them of no use to an online shop. Listing them
all in the back office would bury the few a shop uses, and each one offered needs a label, a group
and the knowledge of which identifiers it reads. Offering only a few would leave a shop stuck as
soon as it uses another service.

## Decision

The plugin ships a selection of services oriented to Sylius shops: audience measurement,
advertising and retargeting, embedded media, social networks, support chat and the like. Any other
service of the vendored library is declared by the shop in bundle configuration, under `trackers:`,
with its group, label and parameters, without PHP. The container build fails when a declared job
key is not a service of the vendored library, when a parameter is not a key that service reads, or
when the job key is already taken. What configuration cannot express - an admin hint, a consent-mode
link, a parameter stored under another name - takes a PHP class of the shop, which joins the
others through autoconfiguration. For an embedded service, the theme writes the placeholders the
library replaces; the plugin only pushes the job. A library upgrade does not add its new services
to the selection: adding one is a change of its own.

## Consequences

- The back office lists the services a shop is likely to use, plus the ones it declares.
- A typo in a declared service fails the deployment instead of giving a service that never loads.
- A new upstream service is not built in until the plugin adds it, but a shop can declare it at once.

## Related

- [0003. Every upstream init option and built-in service key is exposed or listed as a gap](0003-every-upstream-option-exposed-or-listed-as-a-gap.md)
- [0008. One tracker per job key, duplicates refused](0008-one-tracker-per-job-key-duplicates-refused.md)
- [0031. A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md)
- [0037. The scope stops at the free library's Sylius wiring](0037-scope-limited-to-the-free-library-wiring.md)
