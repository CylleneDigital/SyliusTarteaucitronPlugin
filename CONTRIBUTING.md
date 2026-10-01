# Contributing

Thanks for your interest in this plugin. Issues and pull requests are welcome.

**A security flaw is not reported through an issue** — see [SECURITY.md](SECURITY.md).

## Setting up the development environment

The plugin is exercised against Sylius through [`sylius/test-application`](https://github.com/Sylius/TestApplication)
(configured under `tests/TestApplication/`).

```bash
composer install
```

Unit tests do not need a database. Integration tests and Behat boot the test-application kernel
and need a database.

### Database on the host

`tests/TestApplication/.env` points at `mysql://root@127.0.0.1/…` and `tests/TestApplication/.env.test`
(test environment) at `mysql://root:root@127.0.0.1/…`, both on the default port 3306. To use the
Compose MySQL below instead (published on host port **3307**, user `root`, empty password), create
`tests/TestApplication/.env.test.local` (gitignored):

```dotenv
DATABASE_URL=mysql://root@127.0.0.1:3307/cyllene_digital_sylius_tarteaucitron_plugin_%kernel.environment%
```

### Docker stack

Ephemeral stack: MySQL + PHP + nginx. `compose.override.yml` is gitignored — copy it from the
dist file (or let `make docker-up` create it). It has **no Chrome service**.

```bash
composer install                 # on the host, or: docker compose exec php composer install
make docker-up                   # copies compose.override.dist.yml if missing, then up -d
                                 # MySQL is published on host port 3307 (3306 is often taken)
make test-app-init               # database + migrations + assets:install (into test-application/public)
make test-app-frontend           # yarn build in vendor/sylius/test-application (admin @ui)
docker compose exec -T php vendor/bin/behat --strict --no-interaction -f progress --tags='~@javascript'
make docker-down                 # stop and remove volumes when finished
```

`make behat-docker` runs **every** scenario, so its `@javascript` ones fail without a Chrome the PHP
container can reach; use the command above, or run the `@javascript` scenarios on the host.

### Dev shop

The test application loads the Sylius `default` fixture suite plus, from
`tests/TestApplication/config/config.yaml`, a second channel `B2B_WEB`, `fr_FR` on `FASHION_WEB`,
and a tarteaucitron configuration with services on for both channels:

```bash
vendor/bin/console sylius:fixtures:load default -n
```

With two channels, Sylius picks the channel from the host name: set the host you browse the shop on
in `tests/TestApplication/.env.dev.local` **before** loading the fixtures (`FASHION_WEB` takes it,
`B2B_WEB` takes `b2b.` + it):

```dotenv
SYLIUS_FIXTURES_HOSTNAME=localhost
```

In the dev environment, `?_channel_code=B2B_WEB` switches the shop to the second channel (kept in a
cookie; `?_channel_code=FASHION_WEB` to come back).

### Behat

17 scenarios under `features/`. 9 use the Mink `symfony` session (no browser); 8 are tagged
`@javascript` and drive a real Chrome: seven shop scenarios (the banner as the library renders it,
the icon, a channel in two locales) and the admin services column. tarteaucitron.js only opens the
banner when an enabled service needs consent, so the banner scenarios enable `youtube`.

Scenarios without a browser:

```bash
vendor/bin/behat --strict --no-interaction -f progress --tags='~@javascript'
```

`@javascript` scenarios need, as configured in `behat.dist.php`:

1. Chrome headless with remote debugging on `127.0.0.1:9222`:

   ```bash
   google-chrome --headless=new --remote-debugging-port=9222 --no-sandbox
   ```

2. the test application served **in the test environment** at `BEHAT_BASE_URL`
   (`http://127.0.0.1:8080/` in `tests/TestApplication/.env`), after `make test-app-init` /
   `make test-app-frontend` or their host equivalents:

   ```bash
   APP_ENV=test php -d variables_order=EGPCS -S 127.0.0.1:8080 -t vendor/sylius/test-application/public
   ```

   The Compose nginx also publishes port 8080: stop the Docker stack first, or serve on another
   port and set the same URL as `BEHAT_BASE_URL` in `tests/TestApplication/.env.test.local`.

Then run the whole suite:

```bash
vendor/bin/behat --strict --no-interaction -f progress
```

Failure screenshots and pages land in `etc/build/`. CI runs every scenario, `@javascript` included
([`SyliusLabs/BuildTestAppAction`](https://github.com/SyliusLabs/BuildTestAppAction) with
`e2e_js: "yes"` starts Chrome and the web server).

## Proposing a change

1. **Fork** the repository, then clone your fork.
2. Branch off `main`: `git switch -c fix/short-description`.
3. Commit in **English**, conventional form: `feat:`, `fix:`, `docs:`, `test:`, `ci:`, `chore:`.
4. Run the checks below (same as CI).
5. Push and open a pull request against `main`.

If this is your first contribution on a public GitHub fork, workflows may wait for a maintainer
to approve the run. That is GitHub's default, not something you did wrong.

Required checks on pull requests:

| Check | What it covers |
|---|---|
| **`Build complete`** | Every job of the matrix (Sylius ~2.1 / ~2.2 / ~2.3 × PHP 8.2–8.5 × Symfony ^7.4 / ^8.0 on MySQL 8.4, within what each Sylius supports, plus MariaDB 11.4 and PostgreSQL 17) passed the CI checks below |
| **`Composer audit`** | known vulnerabilities in dependencies |

## What has to pass before a pull request

The CI checks, in the order CI runs them (`.github/workflows/build.yaml`), with `APP_ENV=test` and a
database built from the migrations (`make test-app-init`):

```bash
vendor/bin/phpunit --colors=always --testsuite=unit
composer validate --ansi --strict
vendor/bin/console lint:container
vendor/bin/console doctrine:schema:validate --skip-sync
vendor/bin/console doctrine:schema:update --dump-sql | grep -i cyllene_tarteaucitron
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
vendor/bin/ecs check --no-progress-bar
vendor/bin/phpunit --colors=always --testsuite=integration
vendor/bin/behat --colors --strict --no-interaction -f progress
```

The `doctrine:schema:update --dump-sql | grep` line is the migration / mapping drift check: it must
print **nothing**. Any SQL for a `cyllene_tarteaucitron_*` table means the migration and the entity
mapping have diverged. Behat prerequisites: [Behat](#behat).

Make targets: `make test-unit`, `make test-integration`, `make behat`, `make behat-docker`,
`make docker-up`, `make docker-down`, `make test-app-init`, `make test-app-frontend`,
`make lint-container`, `make phpstan`, `make ecs`.

Releasing (maintainers): [docs/release.md](docs/release.md).

## Conventions

- **PHPStan level `max`** on `src/` (`phpstan.neon`). Lowering the level or adding `ignoreErrors`
  needs a justification in the PR.
- **ECS** via `sylius-labs/coding-standard` (`ecs.php`).
- **Tests are mandatory** for bug fixes: the test must fail before the fix. Prefer a Behat scenario
  when the bug is visible in the shop or back office.
- Update **`UPGRADE.md`** and **`CHANGELOG.md`** when the public contract changes. Public contract
  items are listed in the README and in [docs/versioning.md](docs/versioning.md); they only break in
  a major version.
- Do not commit customer pre-production URLs, shop secrets, or real tracker IDs.
- Step-by-step guides: [adding a tracker](docs/development/adding-a-tracker.md),
  [an init option](docs/development/adding-an-init-option.md),
  [a bundle config key](docs/development/adding-a-bundle-config-key.md).
- The codebase is written in **English** (code, comments, commits).

## Upgrading tarteaucitron.js

The shop ships a self-hosted copy under `public/tarteaucitron/`. Integrators do not run an update
script: they upgrade this Composer package.

To bump the vendor library, run the update script with the upstream tag (never copy files by
hand), then the unit suite:

```bash
bin/update-tarteaucitron.sh v1.35.0
vendor/bin/phpunit --colors=always --testsuite=unit
```

`VendoredAssetsTest`, `VendorLibraryParityTest` and `VendorInitOptionsParityTest` must stay green. Details:
[docs/integration/assets.md](docs/integration/assets.md); the whole procedure, from the weekly upstream
issue to the release: [docs/maintenance.md](docs/maintenance.md).

## What does not belong here

Shop-specific consent policies, custom themes, or hard-coded third-party scripts stay in the
project that uses the plugin. This repository only carries the Sylius wiring around the free,
self-hosted [tarteaucitron.js](https://github.com/AmauriC/tarteaucitron.js) library.
