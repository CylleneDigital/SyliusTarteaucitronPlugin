# Architecture overview

## Plugin purpose

The plugin integrates [tarteaucitron.js](https://tarteaucitron.io/) into Sylius 2 to manage cookie consent and conditional loading of third-party services (analytics, ads, embeds).

**Core principle:** one configuration **per Sylius channel**. Each channel has at most one `TarteaucitronConfiguration` row in the database, with `init` options (JSON) and a collection of services (trackers).

## Technical stack

| Component | Version |
|-----------|---------|
| PHP | ^8.2 |
| Sylius | ^2.1 (2.1, 2.2, 2.3) |
| Symfony | ^7.4 \|\| ^8.0 (8 with Sylius 2.3+) |
| Doctrine ORM | ^2.14 \|\| ^3.0 |
| DoctrineBundle | ^2.13 \|\| ^3.1 (3.x with Symfony 8) |
| Twig | ^3.10 |
| tarteaucitron.js (vendored) | see `public/tarteaucitron/VERSION` |

## Code layers (`src/`)

```
src/
├── Consent/                    # Domain (no Sylius dependency; constants of the plugin entity only)
│   ├── ComplianceCheck.php     # CNIL guidance warnings (back office, never blocking)
│   ├── ConsentAlert.php        # Google / Bing consent mode vs enabled services
│   ├── InlineJson.php          # JSON flags for inline shop output
│   ├── OptionConflicts.php     # Options tarteaucitron.js ignores in the current setup (back-office notes)
│   ├── VendorLibrary.php       # Compile-time facts on the vendored library (version, languages, fingerprints)
│   ├── Init/                   # tarteaucitron.init() catalog, values, mapping, IntegrationOptions
│   ├── Localized/              # Per-locale links and banner texts, vendor language texts
│   └── Tracker/                # Public tracker API + registry, configured tracker, JS rendering
├── Tracker/                    # Built-in integrations (one class per job key)
│   ├── Ads/
│   ├── Analytic/
│   ├── Api/
│   ├── Other/
│   ├── Social/
│   ├── Support/
│   ├── Video/
│   └── TrackerAdminHints.php
├── Csp/                        # Script nonce providers (fixed, NelmioSecurityBundle) and their interface
├── Sylius/                     # Sylius integration (provider, factory, synchronizer, admin channel, locale)
├── Entity/                     # Doctrine ORM
├── Repository/
├── Form/
│   ├── DataMapper/
│   └── Type/                   # Form types + LinkConstraints (link / icon validation)
├── Twig/                       # Shop + admin Twig extension and runtimes
├── Controller/Admin/           # Single configuration page
├── Menu/                       # Admin menu entry
├── Migrations/
└── DependencyInjection/        # Bundle configuration, extension (prepend), Compiler/UniqueTrackerTypePass
```

Which of these classes are public API is listed in [Public contract](public-contract.md); everything
else is `@internal`.

### Catalog vs persistence separation

| Source | Content | Role |
|--------|---------|------|
| **Code catalog** (`InitOptionCatalog`, classes under `src/Tracker/`, `trackers:` YAML) | Defaults, metadata, field types | Source of truth for supported options and services |
| **Bundle configuration** (`integration:`, `script_nonce` / `script_nonce_provider`, `locale_aliases`) | Theme-level values, same for every channel | Set by the integrator in YAML |
| **Database** (`init_options`, `localized_options`, `parameters` JSON, `consent_lifetime_days`) | Admin-chosen values | Effective per-channel configuration |
| **Shop runtime** (`ConsentConfigurationProvider`) | Catalog + DB + `integration:` merge | Payload injected on the front |

Missing keys in the database are filled from the catalog at read time (upgrade-safe).

## Data flow (summary)

```
┌─────────────────┐     save      ┌──────────────────────────┐
│  Back office    │ ────────────► │  cyllene_tarteaucitron_* │
│  (per channel)  │               │  (Doctrine)              │
└─────────────────┘               └────────────┬─────────────┘
                                               │ read (ChannelContext)
                                               ▼
                                  ┌──────────────────────────────┐
     integration: (YAML) ───────► │ ConsentConfigurationProvider │
                                  │ → ResolvedConsent            │
                                  └────────────┬─────────────────┘
                                               ▼
                         templates/shop/tarteaucitron.html.twig
                                               ▼
                         tarteaucitron.min.js (vendored)
```

The shop template, rendered in the `sylius_shop.base.head` hook, calls in order:

1. `tarteaucitron_enabled()` - nothing is printed when it is false;
2. `tarteaucitron_script_nonce()` - the nonce of the configured provider (per response or fixed), if
   any, on the three scripts and the two script preloads;
3. `tarteaucitron_custom_text()` and `tarteaucitron_init()`, kept in variables (per-locale texts and
   links applied);
4. `<link rel="preload" fetchpriority="low">` for the lang and services files (unless
   `useExternalJs`) and the library stylesheet (unless `useExternalCss`), built with
   `tarteaucitron_language()` and `tarteaucitron_library_version()`;
5. `tarteaucitron_asset_version('css/sylius-fix.css')` - `?v=` fingerprint of the stylesheet URL;
6. `tarteaucitron_language()` → `tarteaucitronForceLanguage`;
7. `tarteaucitron_consent_lifetime_days()` → `tarteaucitronForceExpire`;
8. the texts of step 3 → `tarteaucitronCustomText` (only when the admin set texts for the current
   locale);
9. `tarteaucitron_asset_version('tarteaucitron.min.js')` - `?v=` fingerprint of the library URL;
10. the payload of step 3 → `tarteaucitron.init({...})`;
11. `tarteaucitron_tracker_scripts()` → `tarteaucitron.user.*` assignments and `job.push()` calls.

Every value is printed through the `tarteaucitron_json` filter. Details:
[Twig functions](../shop/twig-functions.md), [Shop sequence](../diagrams/shop-render-sequence.md).

## Extension points

1. **Custom tracker** - `trackers:` YAML entry, or a class extending `AbstractTrackerDefinition`
   (DI tag `cyllene_digital_sylius_tarteaucitron.tracker`, added by autoconfiguration)
2. **Bundle configuration** - `script_nonce`, `script_nonce_provider`, `locale_aliases`, `integration`, `trackers`
3. **Shop theme** - override `templates/shop/tarteaucitron.html.twig`, or move / disable the
   `tarteaucitron` hookable; embed placeholders + `tarteaucitron_is_embed()` helper

`ConsentConfigurationProviderInterface`, `TrackerRegistry` and the other services are
internal: decorating or replacing them is not covered by semver.

## Design decisions

The main choices and where they show in the code; every decision, with its reasons, is in the
[decision records](../adr/README.md).

| Decision | In the code |
|---|---|
| [tarteaucitron.js is vendored and self-hosted, never loaded from a CDN](../adr/0001-library-vendored-and-self-hosted.md) | `public/tarteaucitron/`, replaced as a whole by `bin/update-tarteaucitron.sh`; the template links it with `asset()` |
| [Per-channel options in the back office, theme-level options in configuration](../adr/0006-channel-options-in-back-office-theme-options-in-configuration.md) | `InitOptionCatalog` (back office, `init_options` column) vs `IntegrationOptions` (`integration:` node), merged by `ConsentConfigurationProvider` |
| [Plugin-owned tables, one configuration per channel](../adr/0010-plugin-owned-tables-one-configuration-per-channel.md) | `TarteaucitronConfiguration` unique on the channel, `TarteaucitronService` unique on (configuration, type); `enabled` defaults to `false` |
| [Every value written into JavaScript goes through InlineJson](../adr/0016-javascript-values-encoded-by-inline-json.md) | `InlineJson::encode()`, exposed as the `tarteaucitron_json` filter and used by `TrackerScriptRenderer` |
| [The shop reads the enabled services as arrays, once per request](../adr/0020-shop-reads-enabled-services-as-arrays-per-request.md) | `findShopConsentByChannel()` (arrays, enabled services only), kept per channel by `ConsentConfigurationProvider` (`kernel.reset`) |
| [Back-office notices guide, never block](../adr/0024-back-office-notices-guide-never-block.md) | `ComplianceCheck` (CNIL), `OptionConflicts` (library limits), `ConsentAlert` (consent modes): rendered by the admin runtime, no validation constraint |
| [A domain layer without Sylius dependency](../adr/0030-domain-without-sylius-dependency.md) | `src/Consent/` imports nothing from Sylius; `src/Sylius/` resolves channel and locale |
| [A small public API: stored keys are the contract](../adr/0031-small-public-api-stored-keys-are-the-contract.md) | Listed in [Public contract](public-contract.md); everything else carries `@internal` |
| [Twig hooks are prepended by the extension](../adr/0035-twig-hooks-prepended-by-the-extension.md) | `CylleneDigitalSyliusTarteaucitronExtension::prepend()`: Doctrine mapping, migrations path, `config/twig_hooks/**/*.yaml` |

## Entry files

| File | Role |
|------|------|
| `src/CylleneDigitalSyliusTarteaucitronPlugin.php` | Symfony Bundle class |
| `src/DependencyInjection/CylleneDigitalSyliusTarteaucitronExtension.php` | Service loading, Doctrine/Twig Hooks prepend |
| `src/DependencyInjection/Compiler/UniqueTrackerTypePass.php` | Fails the container build on a duplicated job key, and gives the tracker registry its arguments (locator keyed by job key) |
| `config/services.php` | Imports `config/services/*.php` |
