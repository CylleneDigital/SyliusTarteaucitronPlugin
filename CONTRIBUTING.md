# Contributing

Thanks for your interest in this plugin. Issues and pull requests are welcome.

**A security flaw is not reported through an issue** - see [SECURITY.md](SECURITY.md).

## Setting up the development environment

Fork `CylleneDigital/SyliusTarteaucitronPlugin` on GitHub, then clone your fork and keep the
original repository as `upstream`:

```bash
git clone git@github.com:<your-account>/SyliusTarteaucitronPlugin.git
cd SyliusTarteaucitronPlugin
git remote add upstream git@github.com:CylleneDigital/SyliusTarteaucitronPlugin.git
composer install
```

The plugin is exercised against Sylius through [`sylius/test-application`](https://github.com/Sylius/TestApplication)
(configured under `tests/TestApplication/`).

Unit tests do not need a database. Integration tests and Behat boot the test-application kernel
and need a database.

With PHP, a database and Node on the host, one command prepares the test application for the
environment in `APP_ENV`: it **drops and recreates the database**, runs the migrations, loads the
fixtures, builds the front end and runs `assets:install`:

```bash
composer run test-app-init
```

`make test-app-init` (Docker stack, below) is lighter: it creates the database if missing, runs the
migrations and `assets:install`, without fixtures or front-end build (`make test-app-frontend`).

### Database on the host

`tests/TestApplication/.env` points at `mysql://root@127.0.0.1/…` and `tests/TestApplication/.env.test`
(test environment) at `mysql://root:root@127.0.0.1/…`, both on the default port 3306. To use the
Compose MySQL below instead (published on host port **3307**, user `root`, empty password), create
`tests/TestApplication/.env.test.local` (gitignored):

```dotenv
DATABASE_URL=mysql://root@127.0.0.1:3307/cyllene_digital_sylius_tarteaucitron_plugin_%kernel.environment%
```

### Docker stack

Ephemeral stack: MySQL + PHP + nginx. `compose.override.yml` is gitignored - copy it from the
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

Sylius picks the channel from the host name. `FASHION_WEB` takes `SYLIUS_FIXTURES_HOSTNAME`, which
defaults to `localhost` (`127.0.0.1` matches it too), and `B2B_WEB` takes `b2b.` + it: browsing on
`localhost`, as the Docker stack and the PHP built-in server do, needs no setting. Only for another
host name (a local domain behind a reverse proxy), set it **before** loading the fixtures, in
`tests/TestApplication/.env.dev.local` (`.env.test.local` with the Docker stack, which runs in the
test environment):

```dotenv
SYLIUS_FIXTURES_HOSTNAME=shop.example.localhost
```

In debug mode, `?_channel_code=B2B_WEB` also switches the shop to the second channel (kept in a
cookie; `?_channel_code=FASHION_WEB` to come back).

### Behat

22 scenarios under `features/`. 10 use the Mink `symfony` session (no browser); 12 are tagged
`@javascript` and drive a real Chrome: eleven shop scenarios (the banner as the library renders it,
accepting and denying from it, the icon, a channel in two locales, services accepted or denied by
default, the preloaded library files) and the admin services column. tarteaucitron.js only opens the
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

`main` is protected. Every change goes through a pull request from a fork, a one-line documentation
fix included, and the maintainers work the same way.

1. Branch off an up-to-date `main`, one topic per branch:

   ```bash
   git fetch upstream
   git switch -c fix/short-description upstream/main
   ```

2. Make the change, with its test (see [Conventions](#conventions)), and run the
   [checks](#what-has-to-pass-before-a-pull-request): they are the ones the CI runs.
3. Commit in **English**, conventional form (`feat:`, `fix:`, `docs:`, `test:`, `ci:`, `chore:`),
   imperative mood, then push the branch to your fork:

   ```bash
   git commit -m "fix: keep the banner title on one line"
   git push -u origin fix/short-description
   ```

4. Open the pull request against `main` of `CylleneDigital/SyliusTarteaucitronPlugin` (GitHub offers
   *Compare & pull request* on your fork). Use the commit message as the title, and fill in the
   template: why the change, what it changes for a shop, how you checked it. Add a before / after
   screenshot for a visible change to the banner, the panel or the back office, and `Closes #<issue>`
   when it fixes an issue.
5. If `main` moves before the merge, rebase rather than merge it into your branch:

   ```bash
   git fetch upstream
   git rebase upstream/main
   git push --force-with-lease
   ```

Pushing to your fork runs nothing: the workflows start when the pull request is opened. On a first
contribution they also wait for a maintainer to approve the run - GitHub's default on public
repositories, not something you did wrong.

Two checks have to be green, and the branch up to date with `main`, before a pull request can be
merged:

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
vendor/bin/console lint:twig templates
vendor/bin/console doctrine:schema:validate --skip-sync
vendor/bin/console doctrine:migrations:execute 'CylleneDigital\SyliusTarteaucitronPlugin\Migrations\Version20260827140000' --down -n
vendor/bin/console doctrine:migrations:execute 'CylleneDigital\SyliusTarteaucitronPlugin\Migrations\Version20260827140000' --up -n
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

`vendor/bin/ecs check --fix` fixes the style automatically. The standard is
[`sylius-labs/coding-standard`](https://github.com/Sylius-Labs/CodingStandard), the one used by
Sylius plugins, do not add personal rules to it.

## Conventions

- **PHPStan level `max`** on `src/` and `tests/` (`phpstan.neon`, test application fixture included).
  Lowering the level or adding `ignoreErrors` needs a justification in the PR.
- **ECS** via `sylius-labs/coding-standard` (`ecs.php`).
- **Tests are mandatory** for bug fixes: the test must fail before the fix. Prefer a Behat scenario
  when the bug is visible in the shop or back office.
- Update **`UPGRADE.md`** when the public contract changes; the change itself is described in the
  pull request, which feeds the release notes. Public contract items are listed in
  [docs/architecture/public-contract.md](docs/architecture/public-contract.md); they only break in
  a major version.
- Do not commit customer pre-production URLs, shop secrets, or real tracker IDs.
- Step-by-step guides: [adding a tracker](docs/development/adding-a-tracker.md),
  [an init option](docs/development/adding-an-init-option.md),
  [a bundle config key](docs/development/adding-a-bundle-config-key.md).
- The codebase is written in **English** (code, comments, commits).
- Never edit `public/tarteaucitron/` by hand: the vendored library is only replaced by
  `bin/update-tarteaucitron.sh` ([details](docs/integration/assets.md#upgrading-tarteaucitronjs));
  `css/sylius-fix.css` is the plugin's own file.

## What does not belong here

Shop-specific consent policies, custom themes, or hard-coded third-party scripts stay in the
project that uses the plugin. This repository only carries the Sylius wiring around the free,
self-hosted [tarteaucitron.js](https://github.com/AmauriC/tarteaucitron.js) library.
