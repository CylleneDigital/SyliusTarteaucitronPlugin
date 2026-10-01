# Adding a bundle configuration key

Integrator-facing reference of the existing keys: [integration/configuration.md](../integration/configuration.md).
Add every new key there too.

Bundle configuration is what an **integrator** sets in YAML, per environment, for every channel:

```yaml
# config/packages/cyllene_digital_sylius_tarteaucitron.yaml
cyllene_digital_sylius_tarteaucitron:
    script_nonce_provider: nelmio
    locale_aliases:
        nb: no
```

## Bundle config or init option?

| Need | Where |
|---|---|
| A `tarteaucitron.init()` option a shop admin tunes per channel | Init option — [adding-an-init-option.md](adding-an-init-option.md) |
| Infrastructure the admin must not change (CSP nonce, locale mapping, paths) | Bundle config — this page |
| A value that differs between environments (`%env()%`) | Bundle config — this page |
| A theme-level `tarteaucitron.init()` option (same for every channel) | A key under `integration:` — [adding-an-init-option.md](adding-an-init-option.md#theme-level-option-integration) |

## Checklist

1. **Declare the node** in `Configuration::getConfigTreeBuilder()`
   (`src/DependencyInjection/Configuration.php`) with a **default** (the key must be optional: an
   integrator who never writes it keeps the current behaviour) and an `->info()` line. The tree is
   strict: an unknown key is rejected at container build, not ignored.
2. **Expose it as a container parameter** in
   `CylleneDigitalSyliusTarteaucitronExtension::load()`, named
   `cyllene_digital_sylius_tarteaucitron.<key>`, with a narrowed type (PHPStan `max` sees
   `$config` as `mixed`):

   ```php
   /** @var array<string, mixed> $integration */
   $integration = $config['integration'];
   $container->setParameter(
       'cyllene_digital_sylius_tarteaucitron.integration_init',
       IntegrationOptions::toInit($integration),
   );
   ```

   A value that must change per request (like the CSP nonce) is not a parameter: register a
   service instead, as `registerScriptNonceProvider()` does for `script_nonce_provider`.

3. **Inject the parameter** into the service that needs it, in `config/services/*.php`
   (never read it back from the container at runtime):

   ```php
   ->arg('$localeAliases', param('cyllene_digital_sylius_tarteaucitron.locale_aliases'))
   ```

   Give the constructor argument the same default as the tree, so the class stays usable in unit
   tests without a kernel.
4. **Test.**
   - Unit: the consuming class with and without a value (see `TarteaucitronRuntimeTest` for
     the nonce providers, `TarteaucitronLanguageResolverTest` for `locale_aliases`).
   - Integration: the parameter and its default in
     `tests/Integration/ServiceWiringTest::testBundleConfigurationParametersAreRegistered()`.
5. **Document.**
   - [integration/configuration.md](../integration/configuration.md): the key, its default, when to
     use it, and a YAML example (every block there must load through the extension).
   - [architecture/bootstrap.md](../architecture/bootstrap.md): the `Configuration` tree line.
   - [architecture/public-contract.md](../architecture/public-contract.md): the key, since
     integrators write it.
   - `UPGRADE.md` (optional key, default keeps current behaviour) and a `CHANGELOG.md` line.
   - The Flex recipe's commented `config/packages/cyllene_digital_sylius_tarteaucitron.yaml`, on
     `symfony/recipes-contrib` (see [release.md](../release.md#after-the-tag)).
6. Run the checks:

```bash
vendor/bin/phpunit --colors=always
vendor/bin/console lint:container
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/ecs check
```

Check the result as an integrator sees it:

```bash
vendor/bin/console config:dump-reference cyllene_digital_sylius_tarteaucitron
```

## Public contract

Configuration keys are written in application YAML: renaming or removing one, or changing its
default in a way that changes behaviour, is a major version. Adding an optional key is a minor.
