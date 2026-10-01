# Services & dependency injection

All the services below are **internal**: ids, classes and constructor arguments can change in a
minor version. The public extension points are the tracker API and DI tag, the bundle configuration
and the shop Twig functions — see [Public contract](../architecture/public-contract.md).

## Entry point

`config/services.php` imports the PHP configurators under `config/services/`.

## Main services

| ID | Class | Public |
|----|-------|--------|
| `cyllene_digital_sylius_tarteaucitron.init.catalog` | `InitOptionCatalog` | no |
| `cyllene_digital_sylius_tarteaucitron.init.mapper` | `InitOptionsMapper` | no |
| `cyllene_digital_sylius_tarteaucitron.tracker.registry` | `TrackerRegistry` | no |
| `cyllene_digital_sylius_tarteaucitron.tracker.script_renderer` | `TrackerScriptRenderer` | no |
| `cyllene_digital_sylius_tarteaucitron.provider.consent` | `ConsentConfigurationProvider` | no |
| `cyllene_digital_sylius_tarteaucitron.factory.configuration` | `TarteaucitronConfigurationFactory` | no |
| `cyllene_digital_sylius_tarteaucitron.synchronizer.services` | `ServiceCatalogSynchronizer` | no |
| `cyllene_digital_sylius_tarteaucitron.admin.channel_resolver` | `AdminChannelResolver` | no |
| `cyllene_digital_sylius_tarteaucitron.locale.language_resolver` | `TarteaucitronLanguageResolver` (depends on `sylius.context.locale`) | no |
| `cyllene_digital_sylius_tarteaucitron.repository.configuration` | `TarteaucitronConfigurationRepository` | no |
| `cyllene_digital_sylius_tarteaucitron.catalog_defaults` | `CatalogConsentDefaults` | no |
| `cyllene_digital_sylius_tarteaucitron.twig.runtime` | `TarteaucitronRuntime` (shop) | no |
| `cyllene_digital_sylius_tarteaucitron.twig.admin_runtime` | `TarteaucitronAdminRuntime` (admin) | no |

Most have an FQCN alias for autowiring inside the plugin.

## Interface aliases

| Interface | Alias to |
|-----------|----------|
| `ConsentConfigurationProviderInterface` | `...provider.consent` |
| `TrackerRegistryInterface` | `...tracker.registry` |
| `TarteaucitronConfigurationRepositoryInterface` | `...repository.configuration` |

## TrackerRegistry — tagged iterator

```xml
<argument type="tagged_iterator" tag="cyllene_digital_sylius_tarteaucitron.tracker" />
```

Aggregates:

1. Built-in trackers, tagged by the `src/Tracker/**/*Tracker.php` prototype
2. `ConfiguredTracker` services, one per `trackers:` entry (`cyllene_digital_sylius_tarteaucitron.tracker.configured.<jobKey>`)
3. Application trackers (autoconfigured, or tagged by hand)

Order not guaranteed — registry indexes by `type`. A job key used twice fails the container build
(`UniqueTrackerTypePass`, see [Bootstrap](../architecture/bootstrap.md#bundle-class)).

## ConsentConfigurationProvider

Dependencies:

- `TarteaucitronConfigurationRepositoryInterface`
- `sylius.context.channel` (Sylius ChannelContext)
- `InitOptionCatalog`
- `InitOptionsMapper`
- `%cyllene_digital_sylius_tarteaucitron.integration_init%` (the `integration:` options as jsKeys)

Implements `ResetInterface` to clear cache between requests. Tagged `kernel.reset` in `config/services/provider.php` (the plugin services are not autoconfigured).

## Substitution in host application

The consent provider, the registry and the other services are internal, not extension points.
Decorating or re-aliasing them (for example `ConsentConfigurationProviderInterface`) works with
standard Symfony mechanisms, but their signatures and ids may change in a minor version: such code
is not covered by semver and must be re-checked on every upgrade.

Prefer the public extension points: a [custom tracker](custom-tracker.md), the
[bundle configuration](configuration.md), or overriding / moving the shop template through its
Twig hook ([Injection](../shop/injection.md)).

## Admin controller

`TarteaucitronConfigurationAction` — readonly invokable, wired in `config/services/controller.php`.

## Menu

`AdminMenuListener` listens to Sylius Admin menu event (`config/services/menu.php`).

## Twig

Runtimes registered as services in `config/services/twig.php` — required for Twig 3 lazy-loading.
The shop runtime receives `cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider`, a
`ScriptNonceProviderInterface` (static from `script_nonce`, NelmioSecurityBundle adapter, or an
alias to the service named in `script_nonce_provider`).
Shop functions and the `tarteaucitron_json` filter are public; admin functions are internal — see
[Twig functions](../shop/twig-functions.md).

## Form

Types + DataMappers in `config/services/form.php`.

## Built-in trackers

Registered via `config/services/trackers.php` prototype (`src/Tracker/**/*Tracker.php`) with tag
`cyllene_digital_sylius_tarteaucitron.tracker`. Symfony IDs are the FQCNs (e.g. `…\Tracker\Analytic\GtagTracker`).
