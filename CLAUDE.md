# CLAUDE.md

Guide for coding agents working on this repository. It complements, without repeating them:

- [`README.md`](README.md) - what the plugin does, installation, configuration, public contract
- [`CONTRIBUTING.md`](CONTRIBUTING.md) - bringing up the environment, QA commands, PR conventions
- [`docs/`](docs/) - technical documentation ([`docs/README.md`](docs/README.md) is the index),
  including the maintainer pages [`docs/release.md`](docs/release.md) and
  [`docs/maintenance.md`](docs/maintenance.md)

This file only holds what reading the code will not tell you.

The plugin targets Sylius 2.1 to 2.3, Symfony 7.4 and 8 (8 with Sylius 2.3 only), PHP 8.2+. It
performs the free, self-hosted tarteaucitron.js install: the library is **vendored**, never loaded
from a CDN.

## Where things live

| Path | Role |
|---|---|
| `src/Consent/` | **Domain** - init options catalog, trackers, CNIL checks (`ComplianceCheck`), library limits (`OptionConflicts`), JSON encoding. No Sylius dependency beyond entity constants |
| `src/Consent/Init/` | `InitOptionCatalog` (every back-office `tarteaucitron.init()` option), `IntegrationOptions` (theme-level options, from the `integration:` bundle configuration) |
| `src/Tracker/` | Built-in services, one class per tarteaucitron job key, picked up by the prototype in `config/services/trackers.php` |
| `src/Sylius/` | Provider of the consent configuration for the current channel, factory, channel resolution for the back office |
| `src/Twig/` | Shop runtime (`tarteaucitron_*` functions, public API) and admin runtime (internal) |
| `src/Csp/` | CSP nonce providers (fixed, NelmioSecurityBundle, or the host's service) |
| `config/services/*.php` | Service definitions - PHP only, Symfony 8 no longer loads XML |
| `config/twig_hooks/` | Shop hook (`sylius_shop.base.head`) and back-office page hooks, globbed by the extension's `prepend()` |
| `templates/shop/tarteaucitron.html.twig` | The official install snippet: globals, library, `init()`, service jobs |
| `public/tarteaucitron/` | **Vendored library** - only replaced by `bin/update-tarteaucitron.sh`. `css/sylius-fix.css` is ours |
| `src/Migrations/` | The plugin's single migration, written against the DBAL `Schema` API |
| `tests/Unit/`, `tests/Integration/` | Domain without a kernel / container wiring through the test application |
| `features/`, `tests/Behat/` | Behat scenarios, contexts, pages; configuration in `behat.dist.php` |
| `tests/TestApplication/` | Throwaway Sylius application - test and dev configuration, plus a fixture (`src/Fixture/`) that seeds a tarteaucitron configuration per channel |

## Commands

The QA commands are in [`CONTRIBUTING.md`](CONTRIBUTING.md). What it does not say:

- The console is the test app's proxy: `vendor/bin/console` (no `bin/console` at the root).
- The public directory served by the test app is `vendor/sylius/test-application/public`; the
  plugin's files reach it through `assets:install`, so re-run it after touching `public/`.
- `composer.lock` is not versioned and `composer.json` has no `config.platform`: CI resolves each
  job with its real PHP version.
- `@javascript` scenarios need a Chrome with remote debugging on `127.0.0.1:9222` and the test
  application served **in the test environment** at `BEHAT_BASE_URL`.

## Known pitfalls

- **Behat 4 reads neither YAML configuration nor annotations.** Hence `behat.dist.php`,
  `tests/Behat/Resources/suites.php` and `#[Given]` attributes. Sylius 2.1 / 2.2 still declare their
  own contexts in annotations: CI requires Behat 3 for those lines (`build.yaml`), and the Sylius
  suites are not imported (2.1 / 2.2 ship them in YAML).
- `tests/TestApplication/config/services_test.php` imports Sylius' Behat services as PHP when the
  file exists (2.3+) and as XML otherwise: Symfony 8 cannot load the XML one.
- **Sylius migrations on MariaDB with DBAL 4** skip themselves (`isMySql()` is false for
  `MariaDBPlatform`), so `sylius_channel` never exists and the plugin migration fails on its
  foreign key. DBAL 4 comes with Symfony 8 / Sylius 2.3: the CI MariaDB job stays on Sylius 2.2.
- **PostgreSQL ids**: every supported Sylius generates ids from sequences there; the migration
  creates sequences instead of identity columns, otherwise the drift check fails.
- **tarteaucitron.js wires its buttons 500 ms after inserting them**: a Behat click before that is
  lost. With the ad-blocker detection on (`integration.adblocker`, on in the test environment),
  the library builds its panel late; the template creates `tarteaucitron.job` so the library does
  not crash before wiring anything when no service is enabled. Other library limits:
  [`docs/domain/init-options.md`](docs/domain/init-options.md#library-limits-tarteaucitronjs-1350).
- The library only opens the banner when an enabled service needs consent: banner scenarios enable
  `youtube`.
- A service switched on in the back office with a required parameter left empty is **not**
  emitted on the shop (no job, no category in the panel).
- The dev fixtures create two channels: Sylius then resolves the channel by host name, so
  `SYLIUS_FIXTURES_HOSTNAME` must match the host the shop is browsed on; `?_channel_code=B2B_WEB`
  switches channel in dev.
- `tests/TestApplication/src/.gitignore` ignores everything but `Fixture/`.
- Asset URLs carry a content fingerprint (`?v=`), but the copy in `public/bundles/` only changes
  with `assets:install`.

## Invariants not to break

- **Never patch the vendored library.** Upgrade it with `bin/update-tarteaucitron.sh`;
  `VendorLibraryParityTest` and `VendorInitOptionsParityTest` then say what the plugin must expose.
- **Back office or `integration:`**: an option a shop admin tunes per channel goes in
  `InitOptionCatalog`; an option that depends on the theme, or breaks the banner when wrong, goes
  in `IntegrationOptions` ([`docs/development/adding-an-init-option.md`](docs/development/adding-an-init-option.md)).
- **CNIL warnings never block saving**, and `OptionConflicts` (library limits) stays separate from
  `ComplianceCheck` (CNIL guidance).
- **Every value written into JavaScript** goes through the `|tarteaucitron_json` filter /
  `InlineJson`, never Twig `json_encode`.
- Init option keys are stored in the database and the shop Twig functions are public: the public
  contract ([`docs/versioning.md`](docs/versioning.md)) only breaks in a major version, with an
  entry in `UPGRADE.md`.

## What does not belong here

Shop-specific consent policies, theme styling and hard-coded third-party scripts stay in the
project using the plugin. This repository carries the Sylius wiring around the free tarteaucitron.js
library, nothing else.
