# Maintenance

Maintainers only. What watches the repository on its own, how you hear about it, and what to do
when tarteaucitron.js publishes a new release.

## Automated watches

| Workflow | When (UTC) | What it does |
|---|---|---|
| `.github/workflows/upstream.yaml` | Mondays 07:00, or by hand (**Actions → Upstream tarteaucitron.js → Run workflow**) | Compares `public/tarteaucitron/VERSION` with the latest GitHub release of [AmauriC/tarteaucitron.js](https://github.com/AmauriC/tarteaucitron.js/releases). When they differ, opens **one issue per upstream tag**: *Upgrade vendored tarteaucitron.js to vX.Y.Z* |
| `.github/workflows/security.yaml` | Mondays 07:00, every push to `main` and pull request | `composer audit` on freshly resolved dependencies |
| `.github/dependabot.yml` | Mondays 10:00 | Pull requests for Composer dependencies and GitHub Actions versions |
| `.github/workflows/build.yaml` | Saturdays 01:00, every push to `main` and pull request | The whole CI matrix: catches a new Sylius, Symfony or dependency release that breaks the plugin |

Only `upstream.yaml` watches the vendored library: Dependabot and `composer audit` do not see it,
since it is not a Composer dependency.

### How `upstream.yaml` decides

- Before creating an issue, it searches **open and closed** issues for the same title. An upgrade
  already done, or a version deliberately skipped (issue closed), is never reported again.
- A later upstream release gets its own issue, even if the previous one is still open.
- The job fails, without creating an issue, when the latest upstream tag is not `X.Y.Z` /
  `vX.Y.Z` (pre-release naming, unexpected tag): look at the upstream releases by hand.

## Getting notified

- **Watch the repository** with at least **Custom → Issues** (and **Pull requests** for
  Dependabot). The issue is opened by `github-actions[bot]` and mentions nobody: without a watch,
  it appears silently.
- A **failed scheduled run** (upstream job, weekly build, audit) is emailed to the user who last
  changed the `cron` line of that workflow, not to every maintainer. Check the **Actions** tab
  from time to time, or edit the cron line yourself to receive those emails.
- GitHub **disables scheduled workflows after 60 days without activity** on a public repository.
  On a quiet repository, re-enable them in **Actions → (workflow) → Enable workflow**; otherwise
  the upstream watch silently stops.

## When an upgrade issue opens

1. Read the upstream release notes (linked from the issue): removed or renamed services, new
   `tarteaucitron.init()` options, CSS or markup changes, security fixes.
2. On a branch, vendor the tag with the script, never by hand:

   ```bash
   bin/update-tarteaucitron.sh vX.Y.Z
   ```

   It replaces the whole file set, keeps `css/sylius-fix.css`, and writes `VERSION` and `SOURCE`
   (tag, archive URL, archive sha256). It refuses a tag whose `tarteaucitron.min.js` declares
   another version, or whose layout lacks a file the library loads. An optional second argument
   (expected sha256) refuses an archive that differs, e.g. to re-vendor the exact archive of an
   existing `SOURCE`. Details: [integration/assets.md](integration/assets.md#upgrading-tarteaucitronjs).
3. Run the unit suite:

   ```bash
   vendor/bin/phpunit --colors=always --testsuite=unit
   ```

   - `VendorLibraryParityTest` fails when a built-in service reads a new `user.*` key: expose it
     in the tracker, or list it in that test's `ACKNOWLEDGED_GAPS`.
   - `VendorInitOptionsParityTest` fails on a new `tarteaucitron.init()` option: add it to
     `InitOptionCatalog` (back office, see [adding an init option](development/adding-an-init-option.md))
     or to `IntegrationOptions` (`integration:` configuration), or list it in `ACKNOWLEDGED_GAPS`.
   - `VendoredAssetsTest` fails when `VERSION`, `SOURCE` and the library disagree, or when a file
     the library can request is not shipped.
4. Run Behat, `@javascript` included ([CONTRIBUTING.md](../CONTRIBUTING.md#behat)): the banner,
   the preferences panel and an embed render in a real browser. After a visual change upstream,
   check `css/sylius-fix.css` (it targets the library's ids and classes) on a shop.
5. Open the pull request with a `CHANGELOG.md` line (*tarteaucitron.js X → Y*), and update the
   version shown in the README compatibility table.
6. Once merged, release a patch or minor version ([release.md](release.md)). Shops get the new
   library with `composer update` then `bin/console assets:install` (see [UPGRADE.md](../UPGRADE.md)).

To skip a version, close its issue with the reason: the workflow will not open it again. An
upstream security fix calls for a release without waiting (see [SECURITY.md](../SECURITY.md)).
