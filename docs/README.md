# Technical documentation

Technical documentation for the **cyllene-digital/sylius-tarteaucitron-plugin**.

For installation and day-to-day usage, see the root [README](../README.md).

## Audience

| Profile | Recommended sections |
|---------|---------------------|
| Shop admin (back office) | [Admin user guide](admin/user-guide.md) |
| Sylius integrator (shop) | [Bundle configuration](integration/configuration.md) (full integrator reference), [Shop](shop/), [Embeds](shop/embeds.md), [Custom tracker](integration/custom-tracker.md), [Public contract](architecture/public-contract.md) |
| Ops (deployment) | [Persistence](persistence/), [Assets](integration/assets.md), [UPGRADE](../UPGRADE.md) |
| Plugin contributor | [Architecture](architecture/), [Domain](domain/), [Adding a tracker](development/adding-a-tracker.md), [Adding an init option](development/adding-an-init-option.md), [Adding a bundle config key](development/adding-a-bundle-config-key.md), [Tests](testing/) |

## Table of contents

### Architecture

- [Overview](architecture/overview.md) - layers, namespaces, dependencies
- [Bootstrap & DI](architecture/bootstrap.md) - bundle, Symfony extension, prepend config, service ids
- [Public contract](architecture/public-contract.md) - stable API (semver major), support policy

### Domain (Consent)

- [Init options](domain/init-options.md) - `InitOptionCatalog`, snake_case → jsKey mapping, `IntegrationOptions`
- [Trackers](domain/trackers.md) - registry, one class per service, custom extension, JS rendering
- [Consent mode alerts](domain/consent-alerts.md) - Google / Bing / GTM consistency

### Persistence

- [Database schema](persistence/schema.md) - entities, JSON, constraints
- [Migrations](persistence/migrations.md) - initial migration

### Back office

- [Admin user guide](admin/user-guide.md) - the configuration screen, for shop admins
- [Configuration flow](admin/configuration-flow.md) - controller, channel resolution, factory, synchronizer, sequence diagram
- [Forms](admin/forms.md) - DataMappers, unmapped fields, validation
- [Admin Twig Hooks](admin/twig-hooks-admin.md) - Sylius Admin UI, tabs, services column

### Shop (front)

- [Injection](shop/injection.md) - Twig Hook `sylius_shop.base.head`
- [Twig functions](shop/twig-functions.md) - `tarteaucitron_*` helpers
- [Embeds](shop/embeds.md) - YouTube, Maps, widgets

### Integration & extension

- [Bundle configuration](integration/configuration.md) - YAML reference for integrators, back office vs configuration
- [Custom tracker](integration/custom-tracker.md) - YAML `trackers:` or `TrackerDefinitionInterface` + DI tag
- [Adding a built-in tracker](development/adding-a-tracker.md) - one file under `src/Tracker/`
- [Adding an init option](development/adding-an-init-option.md) - one `InitOptionCatalog` entry + translations, or an `integration:` key
- [Adding a bundle config key](development/adding-a-bundle-config-key.md) - `Configuration` tree → parameter → service
- [Vendored assets](integration/assets.md) - tarteaucitron.js, upgrade

### Testing

- [Tests](testing/unit-tests.md) - unit / integration / Behat, PHPStan `max`, CI

### Diagrams

- [Shop sequence - render](diagrams/shop-render-sequence.md)
