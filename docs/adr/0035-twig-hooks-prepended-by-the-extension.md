# 0035. Twig hooks are prepended by the extension

- Status: accepted
- Date: 2026-10-20

## Context

The plugin needs configuration in other bundles: its Doctrine mapping, the path of its migrations,
and Twig hooks in two places: one hookable added to the Sylius shop head, and the hooks
of its own back-office page. No hook redefines a hookable of the Sylius core: the shop hookable has
a name of its own, and the back-office page disables the generic Sylius hookables it does not want
under its own hook names, which take precedence over the generic ones whatever the load order.
Configuration prepended by a bundle extension is loaded before the application's, so it only loses
to configuration that targets the same hookable.

## Decision

The extension prepends what the plugin needs to the other bundles' configuration: its Doctrine
mapping, its migrations path, and its hook files, globbed and prepended to the Twig hooks
configuration, each only when the bundle it targets is registered. The application imports nothing
for them.

## Consequences

- Installing the plugin needs no mapping, migrations or hook configuration: the routes, the assets
  and running the migration remain.
- An application moves, overrides or disables a plugin hookable in its own Twig hooks
  configuration, which is loaded after the plugin's and wins.
- A plugin hook that had to replace a Sylius core hookable would lose to the Sylius configuration,
  which the application imports after the prepended one: such a hook would need another loading
  mechanism, and a new record.
- The shop hook, its hookable and the back-office hook names are public contract.

## Related

- [0005. The shop snippet follows the official install](0005-shop-snippet-follows-the-official-install.md)
- [0033. A `Bundle` class with `SyliusPluginTrait`, not `AbstractBundle`](0033-bundle-class-with-sylius-plugin-trait.md)
