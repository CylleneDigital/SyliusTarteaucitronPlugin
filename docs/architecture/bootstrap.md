# Bootstrap & dependency injection

## Bundle class

```php
// src/CylleneDigitalSyliusTarteaucitronPlugin.php
final class CylleneDigitalSyliusTarteaucitronPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new UniqueTrackerTypePass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
```

- `SyliusPluginTrait` registers the plugin in the Sylius ecosystem (metadata, conventions).
- `getPath()` points at the package root, so `templates/`, `translations/`, `config/` and `public/`
  are found next to `src/` (Symfony would otherwise look under `src/`).
- `build()` adds `UniqueTrackerTypePass` (`src/DependencyInjection/Compiler/`): it collects every
  service tagged `cyllene_digital_sylius_tarteaucitron.tracker` whose job key is known without running
  the app (`trackers:` entries, and classes whose definition passes no argument, method call,
  factory or configurator, so all built-ins; abstract definitions are skipped) and
  **fails the container build** when two of them share a job key - for instance a `trackers:` entry
  redeclaring a built-in. Without it, one of the two would silently shadow the other. It also sets
  the arguments of `TrackerRegistry` (see [below](#trackerregistry---tracker-locator)).

## Symfony extension (`CylleneDigitalSyliusTarteaucitronExtension`)

The extension extends `Extension` and implements `PrependExtensionInterface`.

### `load()` phase - services

1. **`Configuration` tree** - `script_nonce` / `script_nonce_provider` (exclusive; turned into the
   `…csp.script_nonce_provider` service: fixed value, NelmioSecurityBundle adapter or alias), `locale_aliases` (default `nb` → `no`; keys normalized to lower-case dash form, so
   `nb_NO` and `nb-no` are the same), `integration` (theme-level init options, turned into jsKeys by
   `IntegrationOptions::toInit()` and exposed as the `…integration_init` parameter the consent
   provider merges into every payload) and `trackers` (default none): each entry becomes a `ConfiguredTracker` service tagged
   `cyllene_digital_sylius_tarteaucitron.tracker`, after its job key and `user.*` keys were checked
   against the vendored `tarteaucitron.services.js` (`VendorServiceCatalog`).
   Adding a key: [Adding a bundle config key](../development/adding-a-bundle-config-key.md).
   `config:dump-reference cyllene_digital_sylius_tarteaucitron` prints the tree.
   The extension also reads the vendored library and `public/admin/` once, at compile time
   (`VendorLibrary`): the `…library_version`, `…library_languages`, `…asset_versions` and
   `…admin_asset_versions` parameters, with a `DirectoryResource` so a debug container is rebuilt
   when those files change.

2. **Custom tracker autoconfiguration**

   Any class implementing `TrackerDefinitionInterface` automatically receives the tag
   `cyllene_digital_sylius_tarteaucitron.tracker`.

3. **Built-in trackers**

   `config/services/trackers.php` prototypes `src/Tracker/**/*Tracker.php` and tags each class
   `cyllene_digital_sylius_tarteaucitron.tracker`. Adding a service is a new class, not an Extension change.

4. **Service loading**

   `config/services.php` imports every file under `config/services/`. They are PHP configurators:
   Symfony 8 no longer loads XML service files.

### `prepend()` phase - Symfony/Sylius configuration

| Target extension | Action |
|------------------|--------|
| `sylius_twig_hooks` | Globs every `config/twig_hooks/**/*.yaml` |
| `doctrine` | Registers ORM entity mapping (`src/Entity/`, attributes) |
| `doctrine_migrations` | Registers namespace `CylleneDigital\SyliusTarteaucitronPlugin\Migrations` |

Prepend ensures the plugin works without manual configuration in the host application (except routes and `assets:install`).

## Service files (`config/services/`)

| File | Content |
|------|---------|
| `tracker.php` | `InitOptionCatalog`, `InitOptionsMapper`, `TrackerRegistry`, `TrackerScriptRenderer` |
| `trackers.php` | Prototype of `src/Tracker/**/*Tracker.php` (DI tag) |
| `provider.php` | `ConsentConfigurationProvider` (`kernel.reset`), factory, synchronizer, `AdminChannelResolver` |
| `repository.php` | `ServiceEntityRepository` + `doctrine.repository_service` tag |
| `form.php` | Form types (configuration, localized options, service), DataMappers, `VendorLanguageTexts` |
| `controller.php` | `TarteaucitronConfigurationAction` |
| `twig.php` | Twig extension, shop + admin runtimes, `TarteaucitronLanguageResolver`, `ConsentAlert`, `ComplianceCheck`, `OptionConflicts` |
| `menu.php` | `AdminMenuListener` |

## Service ids

All the services below are **internal**: ids, classes and constructor arguments can change in a
minor version (see [Substitution in host application](#substitution-in-host-application)).

| ID | Class | Public |
|----|-------|--------|
| `cyllene_digital_sylius_tarteaucitron.init.catalog` | `InitOptionCatalog` | no |
| `cyllene_digital_sylius_tarteaucitron.init.mapper` | `InitOptionsMapper` | no |
| `cyllene_digital_sylius_tarteaucitron.consent.alert` | `ConsentAlert` | no |
| `cyllene_digital_sylius_tarteaucitron.consent.compliance_check` | `ComplianceCheck` | no |
| `cyllene_digital_sylius_tarteaucitron.consent.option_conflicts` | `OptionConflicts` | no |
| `cyllene_digital_sylius_tarteaucitron.localized.vendor_texts` | `VendorLanguageTexts` | no |
| `cyllene_digital_sylius_tarteaucitron.tracker.registry` | `TrackerRegistry` | no |
| `cyllene_digital_sylius_tarteaucitron.tracker.script_renderer` | `TrackerScriptRenderer` | no |
| `cyllene_digital_sylius_tarteaucitron.provider.consent` | `ConsentConfigurationProvider` | no |
| `cyllene_digital_sylius_tarteaucitron.factory.configuration` | `TarteaucitronConfigurationFactory` | no |
| `cyllene_digital_sylius_tarteaucitron.synchronizer.services` | `ServiceCatalogSynchronizer` | no |
| `cyllene_digital_sylius_tarteaucitron.admin.channel_resolver` | `AdminChannelResolver` | no |
| `cyllene_digital_sylius_tarteaucitron.locale.language_resolver` | `TarteaucitronLanguageResolver` (depends on `sylius.context.locale`) | no |
| `cyllene_digital_sylius_tarteaucitron.form.type.configuration` | `TarteaucitronConfigurationType` | no |
| `cyllene_digital_sylius_tarteaucitron.form.type.service` | `TarteaucitronServiceType` | no |
| `cyllene_digital_sylius_tarteaucitron.form.type.localized_options` | `LocalizedOptionsType` | no |
| `cyllene_digital_sylius_tarteaucitron.form.data_mapper.configuration` | `TarteaucitronConfigurationDataMapper` | no |
| `cyllene_digital_sylius_tarteaucitron.form.data_mapper.service` | `TarteaucitronServiceDataMapper` | no |
| `cyllene_digital_sylius_tarteaucitron.menu.admin` | `AdminMenuListener` | no |
| `cyllene_digital_sylius_tarteaucitron.repository.configuration` | alias of `TarteaucitronConfigurationRepository`, whose service id is its class name: DoctrineBundle finds repositories by id, `EntityManager::getRepository()` by class | no |
| `cyllene_digital_sylius_tarteaucitron.twig.extension` | `TarteaucitronExtension` | no |
| `cyllene_digital_sylius_tarteaucitron.twig.runtime` | `TarteaucitronRuntime` (shop) | no |
| `cyllene_digital_sylius_tarteaucitron.twig.admin_runtime` | `TarteaucitronAdminRuntime` (admin) | no |
| `TarteaucitronConfigurationAction` (FQCN) | back-office controller, referenced by `config/routes/admin.yaml` | no |

Apart from the repository and the controller, whose ids are their class names, they are wired by
id only, without FQCN or interface aliases: nothing in the plugin is autowired, and an alias would
let the host application autowire these `@internal` classes.

## TrackerRegistry - tracker locator

The service is declared without arguments (`config/services/tracker.php`): `UniqueTrackerTypePass`
sets them. For every tagged tracker whose job key is known at compile time (built-ins and
`trackers:` entries, constructible without arguments), it adds a locator entry keyed by job key;
the shop then only builds the 1–3 trackers it renders. A tracker needing constructor arguments only
reveals its key once built: it comes in a second, plain iterable, built on first need.

Aggregates:

1. Built-in trackers, tagged by the `src/Tracker/**/*Tracker.php` prototype
2. `ConfiguredTracker` services, one per `trackers:` entry (`cyllene_digital_sylius_tarteaucitron.tracker.configured.<jobKey>`)
3. Application trackers (autoconfigured, or tagged by hand)

Order not guaranteed - registry indexes by `type`. A job key used twice fails the container build
([Bundle class](#bundle-class)).

## ConsentConfigurationProvider

Dependencies:

- `TarteaucitronConfigurationRepositoryInterface`
- `sylius.context.channel` (Sylius ChannelContext)
- `InitOptionCatalog`
- `InitOptionsMapper`
- `%cyllene_digital_sylius_tarteaucitron.integration_init%` (the `integration:` options as jsKeys)

Implements `ResetInterface` to clear cache between requests. Tagged `kernel.reset` in `config/services/provider.php` (the plugin services are not autoconfigured).

## Twig runtimes

Runtimes registered as services in `config/services/twig.php` - required for Twig 3 lazy-loading.
The shop runtime receives `cyllene_digital_sylius_tarteaucitron.csp.script_nonce_provider`, a
`ScriptNonceProviderInterface` (static from `script_nonce`, NelmioSecurityBundle adapter, or an
alias to the service named in `script_nonce_provider`).
Shop functions and the `tarteaucitron_json` filter are public; admin functions are internal - see
[Twig functions](../shop/twig-functions.md).

## Substitution in host application

The consent provider, the registry and the other services are internal, not extension points.
Decorating them (for example `cyllene_digital_sylius_tarteaucitron.provider.consent`) works with
standard Symfony mechanisms, but their signatures and ids may change in a minor version: such code
is not covered by semver and must be re-checked on every upgrade.

Prefer the public extension points ([Public contract](public-contract.md)): a
[custom tracker](../integration/custom-tracker.md), the
[bundle configuration](../integration/configuration.md), or overriding / moving the shop template
through its Twig hook ([Injection](../shop/injection.md)).

## Routes

```yaml
# config/routes.yaml → config/routes/admin.yaml
cyllene_digital_sylius_tarteaucitron_admin_configuration:
    path: /tarteaucitron
    methods: [GET, POST, PUT]
```

`config/routes.yaml` prefixes it with the Sylius admin path (`%sylius_admin.path_name%`), from which
Sylius resolves the admin section. `PUT` saves an existing configuration: Sylius' form template
sends `_method=PUT` once the configuration has an id.

## Public assets

Files under `public/` are exposed via:

```bash
bin/console assets:install
```

Web path: `bundles/cyllenedigitalsyliustarteaucitronplugin/tarteaucitron/...`

## Typical startup order

1. Bundle registered in `config/bundles.php`
2. Extension prepend → Doctrine mappings + Twig Hooks + migrations path
3. Extension load → PHP service configurators (including the tracker prototype), `trackers:` services
4. Compiler pass `UniqueTrackerTypePass` → build fails on a duplicated job key; tracker registry wired
5. Doctrine migration executed
6. `assets:install` for tarteaucitron.js
7. First back-office visit seeds disabled services; tarteaucitron stays off until the admin enables it and saves
