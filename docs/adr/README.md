# Architecture decision records

One file per decision: its context, what was decided, what follows from it. A decision is not
edited once accepted: a new record supersedes it, and the old one says so in its status.

A record holds the decision and its reasons, which do not move with the code. What does - file
paths, class lists, figures - is in the [architecture overview](../architecture/overview.md), the
[bootstrap page](../architecture/bootstrap.md), the [public contract](../architecture/public-contract.md)
and the guides, which follow the code: a record that quoted them would be wrong at the next
refactoring, and could not be corrected. A record explains a choice, it promises nothing: only the
public contract says what is stable.

A new record takes the next number, the template of the others (Status, Date, Context, Decision,
Consequences, Related) and a line in the table below.

| # | Decision |
|---|---|
| 0001 | [tarteaucitron.js is vendored and self-hosted, never loaded from a CDN](0001-library-vendored-and-self-hosted.md) |
| 0002 | [The vendored library is never patched](0002-vendored-library-never-patched.md) |
| 0003 | [Every upstream init option and built-in service key is exposed or listed as a gap](0003-every-upstream-option-exposed-or-listed-as-a-gap.md) |
| 0004 | [Upstream behaviour is followed, not modelled](0004-upstream-behaviour-followed-not-modelled.md) |
| 0005 | [The shop snippet follows the official install](0005-shop-snippet-follows-the-official-install.md) |
| 0006 | [Per-channel options in the back office, theme-level options in configuration](0006-channel-options-in-back-office-theme-options-in-configuration.md) |
| 0007 | [A Sylius-oriented service selection, others added by configuration or by a tagged class](0007-sylius-oriented-service-selection-others-added-by-the-application.md) |
| 0008 | [One tracker per job key, duplicates refused](0008-one-tracker-per-job-key-duplicates-refused.md) |
| 0009 | [A service with an empty required parameter is not emitted](0009-service-with-empty-required-parameter-not-emitted.md) |
| 0010 | [Plugin-owned tables, one configuration per channel](0010-plugin-owned-tables-one-configuration-per-channel.md) |
| 0011 | [Init options are a JSON map rewritten whole on save](0011-init-options-json-map-rewritten-whole-on-save.md) |
| 0012 | [Service rows are seeded and never removed](0012-service-rows-seeded-never-removed.md) |
| 0013 | [Choices are stored as strings](0013-choices-stored-as-strings.md) |
| 0014 | [A schema change is a new migration, written against the DBAL Schema API](0014-schema-change-is-a-new-migration.md) |
| 0015 | [The last save wins](0015-last-save-wins.md) |
| 0016 | [Every value written into JavaScript goes through InlineJson](0016-javascript-values-encoded-by-inline-json.md) |
| 0017 | [Links, icon and texts are validated on write and again on read](0017-links-icon-texts-validated-on-write-and-read.md) |
| 0018 | [The CSP nonce comes from a provider, fixed or per response](0018-csp-nonce-from-a-provider.md) |
| 0019 | [No inline script in the back office](0019-no-inline-script-in-back-office.md) |
| 0020 | [The shop reads the enabled services as arrays, once per request](0020-shop-reads-enabled-services-as-arrays-per-request.md) |
| 0021 | [Library facts are computed when the container is compiled](0021-library-facts-computed-at-compile-time.md) |
| 0022 | [Asset URLs are fingerprinted, cascaded files are preloaded](0022-asset-urls-fingerprinted-cascaded-files-preloaded.md) |
| 0023 | [The banner language is forced server-side](0023-banner-language-forced-server-side.md) |
| 0024 | [Back-office notices guide, never block](0024-back-office-notices-guide-never-block.md) |
| 0025 | [Consent lifetime: the CNIL default, capped by the library](0025-consent-lifetime-cnil-default-library-cap.md) |
| 0026 | [Per-locale links and texts are limited to the banner essentials](0026-per-locale-links-and-texts-limited-to-banner-essentials.md) |
| 0027 | [The back office is one page per channel, not a Sylius resource](0027-back-office-one-page-per-channel.md) |
| 0028 | [The back office is organised by intention](0028-back-office-organised-by-intention.md) |
| 0029 | [The form is generated from the option catalog and mapped by data mappers](0029-form-generated-from-the-option-catalog.md) |
| 0030 | [A domain layer without Sylius dependency](0030-domain-without-sylius-dependency.md) |
| 0031 | [A small public API: stored keys are the contract](0031-small-public-api-stored-keys-are-the-contract.md) |
| 0032 | [Services are declared explicitly, without autowiring](0032-explicit-services-no-autowiring.md) |
| 0033 | [A `Bundle` class with `SyliusPluginTrait`, not `AbstractBundle`](0033-bundle-class-with-sylius-plugin-trait.md) |
| 0034 | [Twig functions are served by lazy runtimes](0034-twig-functions-served-by-lazy-runtimes.md) |
| 0035 | [Twig hooks are prepended by the extension](0035-twig-hooks-prepended-by-the-extension.md) |
| 0036 | [The plugin does not cap a dependency for an upstream bug](0036-no-dependency-cap-for-an-upstream-bug.md) |
| 0037 | [The scope stops at the free library's Sylius wiring](0037-scope-limited-to-the-free-library-wiring.md) |
