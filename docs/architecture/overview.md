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
├── Consent/                    # Domain (no Sylius dependency, except entity constants)
│   ├── ComplianceCheck.php     # CNIL guidance warnings (back office, never blocking)
│   ├── ConsentAlert.php        # Google / Bing consent mode vs enabled services
│   ├── InlineJson.php          # JSON flags for inline shop output
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

1. `tarteaucitron_enabled()` — nothing is printed when it is false;
2. `tarteaucitron_script_nonce()` — the nonce of the configured provider (per response or fixed), if any, on the three `<script>` tags;
3. `tarteaucitron_asset_version('css/sylius-fix.css')` — `?v=` fingerprint of the stylesheet URL;
4. `tarteaucitron_language()` → `tarteaucitronForceLanguage`;
5. `tarteaucitron_consent_lifetime_days()` → `tarteaucitronForceExpire`;
6. `tarteaucitron_custom_text()` → `tarteaucitronCustomText` (only when the admin set texts for the
   current locale);
7. `tarteaucitron_asset_version('tarteaucitron.min.js')` — `?v=` fingerprint of the library URL;
8. `tarteaucitron_init()` → `tarteaucitron.init({...})` (per-locale links applied);
9. `tarteaucitron_tracker_scripts()` → `tarteaucitron.user.*` assignments and `job.push()` calls.

Every value is printed through the `tarteaucitron_json` filter. Details:
[Twig functions](../shop/twig-functions.md), [Shop sequence](../diagrams/shop-render-sequence.md).

## Extension points

1. **Custom tracker** — `trackers:` YAML entry, or a class extending `AbstractTrackerDefinition`
   (DI tag `cyllene_digital_sylius_tarteaucitron.tracker`, added by autoconfiguration)
2. **Bundle configuration** — `script_nonce`, `script_nonce_provider`, `locale_aliases`, `integration`, `trackers`
3. **Shop theme** — override `templates/shop/tarteaucitron.html.twig`, or move / disable the
   `tarteaucitron` hookable; embed placeholders + `tarteaucitron_is_embed()` helper

`ConsentConfigurationProviderInterface`, `TrackerRegistryInterface` and the other services are
internal: decorating or replacing them is not covered by semver.

## Entry files

| File | Role |
|------|------|
| `src/CylleneDigitalSyliusTarteaucitronPlugin.php` | Symfony Bundle class |
| `src/DependencyInjection/CylleneDigitalSyliusTarteaucitronExtension.php` | Service loading, Doctrine/Twig Hooks prepend |
| `src/DependencyInjection/Compiler/UniqueTrackerTypePass.php` | Fails the container build on a duplicated job key |
| `config/services.php` | Imports `config/services/*.php` |
