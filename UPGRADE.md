# Upgrade guide

This file lists, one major version at a time, what has to change in a project already using the
plugin.

The plugin stores its configuration in the database, and shop themes call its Twig functions or
override its template: a compatibility break - a renamed option key, a moved Twig hook, a changed
Twig function - can silently hide the banner or change what it loads before consent. Every break
must therefore be written here before it is released, together with the exact steps to carry out.

`v1.0.0` is the first release: there is nothing to upgrade from yet.

## Library upgrades within 1.x

Run `bin/console assets:install` after `composer update` to publish the new library files.

- **tarteaucitron.js 1.36.0**: the library now asks for consent before loading Crisp (`crisp`
  moved to `needConsent: true`). A shop with Crisp enabled no longer shows the chat until the
  visitor accepts the service.
