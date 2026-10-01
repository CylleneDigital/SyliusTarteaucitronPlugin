# Tests

## Running tests

```bash
composer install
vendor/bin/phpunit --testsuite=unit          # tests/Unit (no kernel)
vendor/bin/phpunit --testsuite=integration   # boots sylius/test-application
vendor/bin/behat --strict                    # features/ (see CONTRIBUTING.md for the @javascript prerequisites)
vendor/bin/console lint:container
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/ecs check
```

Database, Docker stack, headless Chrome and web server for Behat: see
[CONTRIBUTING.md](../../CONTRIBUTING.md#behat). Behat scenarios without a browser only:

```bash
vendor/bin/behat --strict --no-interaction -f progress --tags='~@javascript'
```

Configuration: `phpunit.xml.dist`, `behat.dist.php`, `phpstan.neon`, `ecs.php`, `tests/TestApplication/`.

## Philosophy

- **`tests/Unit/`** - Consent domain and light helpers **without** booting Sylius.
- **`tests/Integration/`** - Kernel boot via `sylius/test-application`: DI wiring, routes, tracker
  tags, the shop template rendered through the real Twig extension, the admin form and controller
  (over HTTP), and the repository against the test database (each test rolled back).
- **`features/` + `tests/Behat/`** - 22 Behat `@ui` scenarios (shop banner injection, admin
  configuration). 10 use the Mink `symfony` session (no browser); 12 are tagged `@javascript` and run
  in headless Chrome (`http://127.0.0.1:9222`, `behat.dist.php`) against the test application served
  at `BEHAT_BASE_URL`. tarteaucitron.js only opens the banner when an enabled service needs consent,
  so the banner scenarios enable `youtube`. The test environment turns `integration.adblocker` on
  (`tests/TestApplication/config/config.yaml`), so browser scenarios also run the library's
  late-built panel path; the dev environment leaves it off.

## Coverage by area

| Area | Test files |
|------|------------|
| Init options | `Consent/Init/InitOptionCatalogTest`, `Consent/Init/InitOptionsTest`, `Consent/Init/InitOptionsMapperTest` |
| Vendor parity | `Consent/Init/VendorInitOptionsParityTest` (init options), `Tracker/VendorLibraryParityTest` (`user.*` keys), `Tracker/TrackerDefinitionsParityTest` (built-in count, key trackers) |
| Vendored assets | `Assets/VendoredAssetsTest`, `Consent/VendorLibraryTest` (compile-time version, languages, fingerprints) |
| Localized options | `Consent/Localized/LocalizedOptionsTest`, `Consent/Localized/VendorLanguageTextsTest` |
| Inline JSON | `Consent/InlineJsonTest` |
| Compliance, alerts and library limits | `Consent/ComplianceCheckTest`, `Consent/ConsentAlertTest`, `Consent/OptionConflictsTest` |
| Trackers | `Tracker/TrackerTestKit` (helper), `Consent/Tracker/TrackerParameterTest`, `Consent/Tracker/TrackerRegistryTest`, `Consent/Tracker/TrackerScriptRendererTest` |
| Bundle configuration | `DependencyInjection/BundleConfigurationTest` |
| CSP nonce | `Csp/NelmioScriptNonceProviderTest` |
| Locale | `Sylius/Locale/TarteaucitronLanguageResolverTest` |
| Provider | `Sylius/Provider/ConsentConfigurationProviderTest` |
| Synchronizer | `Sylius/Synchronizer/ServiceCatalogSynchronizerTest` |
| Factory | `Sylius/Factory/TarteaucitronConfigurationFactoryTest` |
| Forms | `Form/LinkConstraintsTest`; the form and its DataMappers end to end in `Integration/Form/TarteaucitronConfigurationTypeTest` |
| Admin | `Sylius/Admin/AdminChannelResolverTest`, `Twig/TarteaucitronAdminRuntimeTest`, `Translations/AdminTranslationsTest` |
| Shop Twig | `Twig/TarteaucitronRuntimeTest` |
| DI / kernel | `Integration/ServiceWiringTest` |
| Shop template | `Integration/Twig/ShopTemplateRenderingTest` (hostile values, nonce, jobs, preloads) |
| Repository | `Integration/Repository/TarteaucitronConfigurationRepositoryTest` |
| Admin form | `Integration/Form/TarteaucitronConfigurationTypeTest` (submission, constraints per field) |
| Admin controller | `Integration/Controller/TarteaucitronConfigurationActionTest` (HTTP: login, save, invalid, re-seeding) |
| Behat shop banner | `features/shop/displaying_consent_banner.feature` (13 scenarios, 11 `@javascript`) |
| Behat admin config | `features/admin/configuring_tarteaucitron.feature` (9 scenarios, 1 `@javascript`) |

Paths are relative to `tests/Unit/` unless stated otherwise.

## Example behaviors tested

### InitOptions - upgrade-safe and safe values

- Unknown keys ignored; missing keys → catalog default; invalid choice → default.
- `javascript:` and protocol-relative (`//host`) links on `privacy_url` / `readmore_link` / `icon_src`
  are blanked; `http(s)` URLs and relative paths are kept.
- `icon_src` accepts a raster base64 `data:image/…` URI, refuses `data:text/html` and SVG data URIs.
- `cookie_name` / `cookie_domain` that would break the cookie fall back to their default.

`LinkConstraintsTest` checks that the form constraints and the read-time normalization accept and
refuse the same values.

### Bundle configuration

`trackers:` entries become tagged trackers; an unknown job key, a `user.*` key the service never
reads, or a job key already used by a built-in fails the container build; `integration:` becomes the
init payload parameter; `locale_aliases` accepts `nb`, `nb-no` and `nb_NO`.

### TrackerRegistry - uniqueness

Duplicate type → exception.

### TrackerScriptRenderer - security & filtering

- Empty required param → no snippet
- Unknown type → no snippet
- Embed without parameters → `job.push` only
- Values JSON-encoded (no HTML / script injection)

### Shop Twig runtime

`tarteaucitron_json` matches `InlineJson`; empty nonce omitted; current-locale links and texts
override the channel ones; asset version follows the file content and stays inside
`public/tarteaucitron/`.

### Vendored assets

`VERSION` matches `tarteaucitron.min.js`; every language in `availableLanguages` plus `advertising.min.js` is shipped.

### Language resolver

`LocaleContextInterface` → tarteaucitron language file; unknown codes / missing locale fall back to `en`.

### ConsentConfigurationProvider - cache & channel

- No config → disabled
- Existing config → mapped init + services (the database wins as a block over catalog defaults)
- Values written outside the back office (raw JSON) cannot break the shop
- `integration:` options join the init payload
- Resettable between requests (`kernel.reset`, checked in `Integration/ServiceWiringTest`)

### ServiceCatalogSynchronizer

- Adds missing trackers, disabled with empty parameters
- Does not duplicate, and leaves a configured service as it is
- Does not remove existing services, rows of removed trackers included
- Creates no row for a tracker whose `isSeededByDefault()` is `false`

## PHPStan

Level **`max`** in `phpstan.neon`. Analyses `src/`, `tests/Unit`, `tests/Integration`, `tests/Behat`
and the test application fixture (`tests/TestApplication/src`).

Tracker parameters are typed as `array<string, string>` end-to-end (entity → runtime → forms).
Form `getData()` is narrowed once in the DataMapper (`is_string`).

## CI

GitHub workflows:

- `.github/workflows/build.yaml` - on push to `main`, pull requests, releases and weekly. Matrix:
  Sylius `~2.1.0` / `~2.2.0` / `~2.3.0` × PHP 8.2 to 8.5 × Symfony `^7.4` / `^8.0` on MySQL 8.4, within
  what each line supports (Symfony 8 from Sylius 2.3 and PHP 8.4; Sylius 2.3 from PHP 8.3), plus
  Sylius `~2.2.0` / PHP 8.4 / Symfony `^7.4` on MariaDB 11.4 and Sylius `~2.3.0` / PHP 8.4 / Symfony
  `^8.0` on PostgreSQL 17. Before Sylius 2.3, the job requires Behat 3 first: Sylius 2.1 / 2.2
  declare their Behat steps in annotations, which Behat 4 no longer reads; it also caps
  `doctrine/orm` below 3.7 there (their mappings fail its validation). Each job: unit
  tests, `composer validate --strict`, `lint:container`, `lint:twig templates`,
  `doctrine:schema:validate --skip-sync`, plugin migration reverted (`down()`) and replayed,
  migration / mapping drift check, PHPStan, ECS, integration tests, Behat (all scenarios,
  `@javascript` included). The **Build complete** job fails unless every matrix job passed.
- **Code coverage** - measured on one job only, Sylius `~2.3.0` / PHP 8.4 / Symfony `^8.0` on
  MySQL 8.4 (pcov), over the unit and integration suites. The summary lands in the job summary, the
  full report (text and Clover) in the `coverage` artifact. The command, as the job runs it:

  ```bash
  vendor/bin/phpunit --colors=never --coverage-clover=var/coverage/clover.xml --coverage-text=var/coverage/coverage.txt
  ```

  Locally it needs pcov or Xdebug in the PHP that runs PHPUnit.
- `.github/workflows/security.yaml` - `composer audit` on push, pull requests and weekly.
- `.github/workflows/upstream.yaml` - weekly: opens an issue when a newer tarteaucitron.js release
  exists than the vendored one.

## Adding a test

1. Place in `tests/Unit/` mirroring `src/` namespace (or `tests/Integration/` for kernel tests)
2. Unit tests: no full Symfony kernel - mocks/stubs for Sylius interfaces
3. Visible in the shop or back office → prefer a Behat scenario (`@javascript` only when the
   behaviour needs JavaScript)
4. Update this document when covering a new area

## Not covered (manual testing)

- Embed DOM (YouTube, Maps) beyond the banner opening
- Multi-channel UI in a full Sylius environment
- Every vendor service actually loading after consent (third-party scripts)
