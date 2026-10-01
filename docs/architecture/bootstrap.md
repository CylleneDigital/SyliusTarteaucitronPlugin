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
  the app (`trackers:` entries and classes constructible without arguments, so all built-ins) and
  **fails the container build** when two of them share a job key — for instance a `trackers:` entry
  redeclaring a built-in. Without it, `TrackerRegistry` would throw on every shop page.

## Symfony extension (`CylleneDigitalSyliusTarteaucitronExtension`)

The extension implements `PrependExtensionInterface` and `Extension`.

### `load()` phase — services

1. **`Configuration` tree** — `script_nonce` / `script_nonce_provider` (exclusive; turned into the
   `…csp.script_nonce_provider` service: fixed value, NelmioSecurityBundle adapter or alias), `locale_aliases` (default `nb` → `no`; keys normalized to lower-case dash form, so
   `nb_NO` and `nb-no` are the same), `integration` (theme-level init options, turned into jsKeys by
   `IntegrationOptions::toInit()` and exposed as the `…integration_init` parameter the consent
   provider merges into every payload) and `trackers` (default none): each entry becomes a `ConfiguredTracker` service tagged
   `cyllene_digital_sylius_tarteaucitron.tracker`, after its job key and `user.*` keys were checked
   against the vendored `tarteaucitron.services.js` (`VendorServiceCatalog`).
   Adding a key: [Adding a bundle config key](../development/adding-a-bundle-config-key.md).
   `config:dump-reference cyllene_digital_sylius_tarteaucitron` prints the tree.

2. **Custom tracker autoconfiguration**

   Any class implementing `TrackerDefinitionInterface` automatically receives the tag
   `cyllene_digital_sylius_tarteaucitron.tracker`.

3. **Built-in trackers**

   `config/services/trackers.php` prototypes `src/Tracker/**/*Tracker.php` and tags each class
   `cyllene_digital_sylius_tarteaucitron.tracker`. Adding a service is a new class, not an Extension change.

4. **Service loading**

   `config/services.php` imports every file under `config/services/`. They are PHP configurators:
   Symfony 8 no longer loads XML service files.

### `prepend()` phase — Symfony/Sylius configuration

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
| `provider.php` | `CatalogConsentDefaults`, `ConsentConfigurationProvider` (`kernel.reset`), factory, synchronizer, `AdminChannelResolver` |
| `repository.php` | `ServiceEntityRepository` + `doctrine.repository_service` tag |
| `form.php` | Form types (configuration, localized options, service), DataMappers, `VendorLanguageTexts` |
| `controller.php` | `TarteaucitronConfigurationAction` |
| `twig.php` | Twig extension, shop + admin runtimes, `TarteaucitronLanguageResolver`, `ConsentAlert`, `ComplianceCheck` |
| `menu.php` | `AdminMenuListener` |

## Routes

```yaml
# config/routes.yaml → config/routes/admin.yaml
cyllene_digital_sylius_tarteaucitron_admin_configuration:
    path: /tarteaucitron
    methods: [GET, POST, PUT]
```

Sylius admin prefix applied by the host application (`_sylius.section: admin`).

## Public assets

Files under `public/` are exposed via:

```bash
bin/console assets:install
```

Web path: `bundles/cyllenedigitalsyliustarteaucitronplugin/tarteaucitron/...`

## Typical startup order

1. Bundle registered in `config/bundles.php`
2. Extension prepend → Doctrine mappings + Twig Hooks + migrations path
3. Extension load → XML services (including tracker prototype), `trackers:` services
4. Compiler pass `UniqueTrackerTypePass` → build fails on a duplicated job key
5. Doctrine migration executed
6. `assets:install` for tarteaucitron.js
7. First back-office visit seeds disabled services; tarteaucitron stays off until the admin enables it and saves
