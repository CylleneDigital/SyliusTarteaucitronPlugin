# Release procedure

Maintainers only. Releases are immutable: anything wrong in a tag stays wrong.

## Before the tag

1. Merge a dedicated PR that updates, if needed:
   - `CHANGELOG.md` (move `[Unreleased]` into the version section with the date)
   - `README.md` compatibility / status
   - `UPGRADE.md` and `SECURITY.md` supported versions
   - the vendored library, only through `bin/update-tarteaucitron.sh` (see
     [integration/assets.md](integration/assets.md#upgrading-tarteaucitronjs))
2. Wait for **Build complete** and **Composer audit** on that PR.
3. On `main`, with the test application built from the migrations (`APP_ENV=test`), confirm:

```bash
composer validate --ansi --strict
vendor/bin/console lint:container
vendor/bin/console doctrine:schema:validate --skip-sync
vendor/bin/console doctrine:schema:update --dump-sql | grep -i cyllene_tarteaucitron
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
vendor/bin/ecs check --no-progress-bar
vendor/bin/phpunit --colors=always --testsuite=unit
vendor/bin/phpunit --colors=always --testsuite=integration
vendor/bin/behat --colors --strict --no-interaction -f progress
```

The `doctrine:schema:update --dump-sql | grep` line must print **nothing** (no drift between the
migration and the mapping on the plugin tables). Behat needs headless Chrome and a web server for the
`@javascript` scenarios: see [CONTRIBUTING.md](../CONTRIBUTING.md#behat).

## Tag and publish

```bash
git tag -a v1.0.0 -m "v1.0.0"
git push origin v1.0.0
```

Create the GitHub release from the tag (notes = CHANGELOG section). Confirm Packagist has picked up
the tag within a few minutes.

## After the tag

- Bump the `[Unreleased]` section in `CHANGELOG.md` on a follow-up commit if needed.
- **First release only**: submit the Flex recipe to `symfony/recipes-contrib` (it needs the
  package on Packagist). The recipe is staged locally under `recipe/` (git-ignored), with the
  recipes-contrib layout, so it is copied as is into a fork of `symfony/recipes-contrib`:

  ```bash
  cp -r recipe/cyllene-digital <recipes-contrib fork>/
  ```

  Files, under `cyllene-digital/sylius-tarteaucitron-plugin/1.0/`:

  | File | Content |
  |---|---|
  | `manifest.json` | registers the bundle (`all`) and copies `config/` to `%CONFIG_DIR%/` |
  | `config/routes/cyllene_digital_sylius_tarteaucitron.yaml` | imports `@CylleneDigitalSyliusTarteaucitronPlugin/config/routes.yaml` |
  | `config/packages/cyllene_digital_sylius_tarteaucitron.yaml` | `cyllene_digital_sylius_tarteaucitron: ~`, then every key **commented out** with its default (the block of [configuration.md](integration/configuration.md#where)) |
  | `post-install.txt` | `assets:install`, `doctrine:migrations:migrate`, and that the plugin stays off until enabled per channel in **Configuration → Tarteaucitron** |

  The configuration keys stay commented out on purpose: a value written in the shop would freeze
  that default, and a later change of default in the plugin would no longer reach the shop. When a
  key is added or a default changes, update that file on `symfony/recipes-contrib` too (next item).

  The `call-qa / Run updated recipes` check fails on every Sylius plugin (bare Symfony skeleton);
  only `call-qa / Run checks` validates the manifest. Once merged, update the README installation
  section (the bundle, routes and configuration file are then created by Flex).
- For a later Flex recipe change, open or update the PR on `symfony/recipes-contrib` separately.
