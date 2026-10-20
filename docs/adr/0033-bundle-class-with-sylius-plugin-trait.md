# 0033. A `Bundle` class with `SyliusPluginTrait`, not `AbstractBundle`

- Status: accepted
- Date: 2026-10-20

## Context

Symfony's `AbstractBundle` puts the configuration tree and the extension in the bundle class. A
Sylius plugin uses `SyliusPluginTrait`, which redefines `getContainerExtension()`: it expects a
classic extension class next to the bundle, and an alias derived from the plugin name.

## Decision

The bundle is a plain `Bundle` with `SyliusPluginTrait`, a classic `Extension` class and a separate
configuration class, the structure the trait expects. The bundle class only adds the plugin's
compiler pass and points its path at the package root, where the templates, translations,
configuration and public files live.

## Consequences

- The extension loads the services and prepends what the plugin configures in other bundles: its
  Twig hooks, its Doctrine mapping and its migrations path.
- The configuration root key is the alias the trait derives from the plugin name.
- The bundle class and its name are public: the application registers the class, and the public
  assets path is derived from its name.

## Related

- [0014. A schema change is a new migration, written against the DBAL Schema API](0014-schema-change-is-a-new-migration.md)
- [0032. Services are declared explicitly, without autowiring](0032-explicit-services-no-autowiring.md)
- [0035. Twig hooks are prepended by the extension](0035-twig-hooks-prepended-by-the-extension.md)
