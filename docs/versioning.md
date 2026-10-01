# Versioning and support

This plugin follows [semantic versioning](https://semver.org). This page states what that promise
covers.

## Public API

A break in any of the following requires a **major** version.

| Area | Contract |
|---|---|
| Tracker PHP API | `AbstractTrackerDefinition` (recommended extension point), `TrackerDefinitionInterface`, `TrackerParameter`, `TrackerCategory`, `ConsentMode`; `Csp\ScriptNonceProviderInterface` |
| Bundle class | `CylleneDigitalSyliusTarteaucitronPlugin` |
| Tracker DI tag | `cyllene_digital_sylius_tarteaucitron.tracker` |
| Bundle configuration keys | `script_nonce`, `script_nonce_provider`, `locale_aliases`, `integration`, `trackers` under `cyllene_digital_sylius_tarteaucitron:` |
| Shop Twig hook | `sylius_shop.base.head` → hookable `tarteaucitron` |
| Shop Twig functions | `tarteaucitron_enabled()`, `tarteaucitron_init()`, `tarteaucitron_language()`, `tarteaucitron_consent_lifetime_days()`, `tarteaucitron_custom_text()`, `tarteaucitron_asset_version()`, `tarteaucitron_script_nonce()`, `tarteaucitron_tracker_scripts()`, `tarteaucitron_is_embed()` |
| Shop Twig filter | `tarteaucitron_json` |
| Admin Twig hooks and route | `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.*`, route `cyllene_digital_sylius_tarteaucitron_admin_configuration` |
| Database | Table names, JSON `init_options` and `localized_options` keys |
| Vendor JavaScript keys (`jsKey`) | Official tarteaucitron keys produced by the mapper |
| Translation key conventions | `cyllene_digital_sylius_tarteaucitron.ui.service_{type}`, `cyllene_digital_sylius_tarteaucitron.ui.{parameter key}` |

A new method may be added to `TrackerDefinitionInterface` in a **minor** version, with a default
implementation in `AbstractTrackerDefinition`. Extend the abstract class; implementing the interface
directly is at your own risk.

Details: [public-contract.md](architecture/public-contract.md).

## Internals

Everything else is internal (most classes carry `@internal`) and can change in a minor version:
init option catalog and `InitOption*` classes, `IntegrationOptions`, `TrackerRegistry*`,
`TrackerRuntimeState`, `ConsentConfigurationProvider*`, `ResolvedConsent`, Doctrine entities and
repository, controller, forms and data mappers, admin Twig functions, templates, DI extension
internals and service ids, built-in tracker classes, and Behat helpers. Decorating an internal service
is possible but not covered by this promise.

## Support policy

| Line | What is covered |
|---|---|
| Latest minor of the current major | Bug and security fixes |
| Previous major | Security only, for 12 months after the next major is released |
| Sylius / PHP / Symfony versions | Those of the continuous integration matrix; support ends when Sylius itself drops a version |

Report security flaws privately — see [SECURITY.md](../SECURITY.md).

## Upgrading

Every compatibility break is written in [UPGRADE.md](../UPGRADE.md) before it is released, with the
exact steps to carry out.
