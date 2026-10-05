# CLAUDE.md

Guide for coding agents working on this repository. It complements, without repeating them:

- [`README.md`](README.md) - what the plugin does, installation, configuration, public contract
- [`CONTRIBUTING.md`](CONTRIBUTING.md) - bringing up the environment, QA commands, PR conventions
- [`docs/`](docs/) - technical documentation ([`docs/README.md`](docs/README.md) is the index)

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
| `public/admin/` | Back-office stylesheet and script (no inline JS on the page), versioned with `?v=` |
| `src/Migrations/` | The plugin's single migration, written against the DBAL `Schema` API, registered through `migrations_paths` |
| `tests/Unit/`, `tests/Integration/` | Domain without a kernel / kernel tests: container wiring, shop template rendering, form, controller over HTTP, repository |
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
- The integration suite needs the migrated test database: the repository, form and controller
  tests write to it inside a transaction rolled back at the end (`WebTestCase` with
  `disableReboot()`, so every request shares that connection).
- Code coverage is only measured in CI, on the Sylius 2.3 / PHP 8.4 / Symfony 8 / MySQL job (pcov).

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
- **`SyliusLabs/BuildTestAppAction` writes `serverVersion=11.4` for MariaDB**: DBAL then picks the
  MySQL platform and the migration check reports the JSON columns as drift. `build.yaml` rewrites
  it to `mariadb-11.4.0` (DBAL wants three version parts after `mariadb-`).
- **Sylius 2.1 / 2.2 mappings fail `doctrine/orm` 3.7 validation** (`ShipmentUnit#shipment`): CI
  caps the ORM below 3.7 for those lines only, the plugin's own constraint stays open. A local
  `vendor/` resolved before 3.7 does not show it.
- **`friends-of-behat/symfony-extension` stays at `^2.6.2`**: 2.7 needs PHP 8.3, and Composer
  (CI and Dependabot alike) resolves the PHP 8.2 lines with 2.6.2. Do not raise it to `^2.7` while
  `php` is `^8.2`.
- **Sylius' `order` fixture is off in the test app** (`order: false`): it fetch-joins product
  translations in the order's locale only and saves an empty `en_US` one, so orders in `fr_FR`
  fail at random. Products only belong to `FASHION_WEB`, so orders cannot move to `B2B_WEB`.
- **Intermittent Behat 502 in CI** ("Symfony Local Web Server: unable to fetch the response from the
  backend: unexpected EOF"): the PHP-FPM worker behind `symfony server:start` segfaults
  (`child exited on signal 11`, PHP 8.3, after ~100 s and many requests), whatever the page; the
  `es_ES` catalogue was ruled out. `build.yaml` turns OPcache off on the build action and prints the
  server logs on failure. If it comes back without OPcache, the next lead is the PHP build itself.
- Dependabot's Composer log shows `ERROR` stack traces on every run: it tries to widen constraints
  to majors needing PHP 8.4 and gives up. Only a failed run or a missing expected PR matters.
- **PostgreSQL ids**: every supported Sylius generates ids from sequences there; the migration
  creates sequences instead of identity columns, otherwise the drift check fails.
- **tarteaucitron.js wires its buttons 500 ms after inserting them**: a Behat click before that is
  lost. With the ad-blocker detection on (`integration.adblocker`, on in the test environment),
  the library builds its panel late; the template creates `tarteaucitron.job` so the library does
  not crash before wiring anything when no service is enabled. Other library limits:
  [`docs/domain/init-options.md`](docs/domain/init-options.md#library-limits-tarteaucitronjs-1350).
- The library only opens the banner when an enabled service needs consent: banner scenarios enable
  `youtube`.
- **tarteaucitron.js DOM traps for tests**: `#tarteaucitronPersonalize2` is the "accept all"
  button and `#tarteaucitronCloseAlert` opens the panel ("Personalize"); the banner title
  (`middleBarHead`) is CSS `::before` content, absent from `innerText`; `.tarteaucitronOpenPanel`
  elements are wired once, on the window `load` event, so one added later does nothing.
- **The admin route accepts `PUT` on purpose**: once the configuration has an id, Sylius' form
  template posts `_method=PUT`, and the form only submits when the methods match.
- A service switched on in the back office with a required parameter left empty is **not**
  emitted on the shop (no job, no category in the panel).
- The dev fixtures create two channels: Sylius then resolves the channel by host name, so
  `SYLIUS_FIXTURES_HOSTNAME` must match the host the shop is browsed on; `?_channel_code=B2B_WEB`
  switches channel in dev.
- `tests/TestApplication/src/.gitignore` ignores everything but `Fixture/`.
- Asset URLs carry a content fingerprint (`?v=`), but the copy in `public/bundles/` only changes
  with `assets:install`. The fingerprints, the library version and its languages are computed when
  the container is compiled (`VendorLibrary`): a `DirectoryResource` recompiles it in debug, a
  production cache needs `cache:clear` after a library upgrade.
- **`UniqueTrackerTypePass` sets the tracker registry's arguments** (the service has none in
  `config/services/tracker.php`): a locator of the trackers whose job key is known at compile time,
  plus an iterable for those needing constructor arguments. A tracker added another way than by the
  tag never reaches the registry.
- **The repository's service id is its class name**: DoctrineBundle indexes tagged repositories by
  service id and `EntityManager::getRepository()` asks for the class, so an alias does not do. The
  `cyllene_digital_sylius_tarteaucitron.repository.configuration` id is the alias.
- **`IDX_TAC_SERVICE_CONFIGURATION` is not redundant** with `uniq_tac_service_type`: the ORM schema
  tool adds the foreign key index first and would recreate it under a generated name (drift).
- **Orphan service rows** (a tracker removed from the code) are printed by the form hook's
  `render_rest` on Sylius 2.1 only (2.2 / 2.3 skip them): `groupServicesByCategory()` marks them
  rendered. Reproducing it needs the 2.1 CI line, a local 2.2 / 2.3 install never shows it.

## Invariants not to break

- **Never patch the vendored library.** Upgrade it with `bin/update-tarteaucitron.sh`;
  `VendorLibraryParityTest` and `VendorInitOptionsParityTest` then say what the plugin must expose.
- **Back office or `integration:`**: an option a shop admin tunes per channel goes in
  `InitOptionCatalog`; an option that depends on the theme, or breaks the banner when wrong, goes
  in `IntegrationOptions` ([`docs/development/adding-an-init-option.md`](docs/development/adding-an-init-option.md)).
- **CNIL warnings never block saving**, and `OptionConflicts` (library limits) stays separate from
  `ComplianceCheck` (CNIL guidance).
- **Every value written into JavaScript** goes through the `|tarteaucitron_json` filter /
  `InlineJson`, never Twig `json_encode`. `ShopTemplateRenderingTest` renders the real template
  with hostile values to hold it.
- **Choices are stored as strings**, but tarteaucitron.js compares some values strictly:
  `InitOptionsMapper` turns `'true'` / `'false'` into booleans (`serviceDefaultState`). A new
  boolean-like choice needs the same, or the library silently ignores it.
- **The form DataMappers are the only source of the fields' data**: the fields added in
  `PRE_SET_DATA` take no `data` / `mapped` option (`Form::setData()` maps right after that event).
- **Schema changes ship as a new migration in `src/Migrations/`**, written against the DBAL
  `Schema` API and kept free of drift on MySQL, MariaDB and PostgreSQL (CI runs `down()` / `up()`
  then the drift check). Never ask the host application to run `doctrine:migrations:diff`.
- Init option keys are stored in the database and the shop Twig functions are public: the public
  contract ([`docs/architecture/public-contract.md`](docs/architecture/public-contract.md)) only
  breaks in a major version, with an entry in `UPGRADE.md`.
- **No `CHANGELOG.md`**: the GitHub release notes are the changelog, `UPGRADE.md` says what a shop
  has to do.
- **The Flex recipe is not in this repository** (it lives in `symfony/recipes-contrib`,
  `cyllene-digital/sylius-tarteaucitron-plugin/1.0/`): a change to the bundle configuration keys or
  defaults, to `config/routes.yaml` or to the install steps (assets, migration) must be carried over
  to it through a pull request there, and to the manual steps of the README `Installation` section.
  Its configuration file stays fully commented, root key included, with `null` rather than `~`: the
  recipes-contrib checks reject an empty root key and any `: ~`, even in a comment.
- **Maintainer procedures stay out of the repository** (release, upstream watch, GitHub settings):
  public docs serve shop admins, integrators and contributors only.

## Keeping the docs in step

The same facts live in several places; change one, change them all:

- the CI steps: `.github/workflows/build.yaml`, `CONTRIBUTING.md` and `docs/testing/unit-tests.md`;
- the shop template: `templates/shop/tarteaucitron.html.twig`, copied verbatim in
  `docs/shop/injection.md` and walked through in `docs/architecture/overview.md` and `docs/diagrams/shop-render-sequence.md`;
- the Behat counts (scenarios, `@javascript`): `README.md`, `CONTRIBUTING.md`,
  `docs/testing/unit-tests.md`;
- the public Twig functions and bundle keys: `docs/architecture/public-contract.md` and
  `docs/shop/twig-functions.md`.

## What does not belong here

Shop-specific consent policies, theme styling and hard-coded third-party scripts stay in the
project using the plugin. This repository carries the Sylius wiring around the free tarteaucitron.js
library, nothing else.
