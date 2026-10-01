# Vendored assets (tarteaucitron.js)

Self-hosted copy of the [official free install](https://tarteaucitron.io/en/free-installation-open-source/).
The library resolves `css/`, `lang/`, `tarteaucitron.services.min.js` and `advertising.min.js`
from `document.currentScript.src`. Files must live next to `tarteaucitron.min.js` — do not
bundle them with Webpack Encore and do not load them from a CDN.

## Location

```
public/
├── admin/
│   └── tarteaucitron-admin.css      # Back-office page styles
└── tarteaucitron/
    ├── VERSION                       # Vendored version (e.g. 1.35.0)
    ├── SOURCE                        # Tag, archive URL and sha256 (bin/update-tarteaucitron.sh)
    ├── LICENSE                       # Upstream MIT notice (must be kept)
    ├── tarteaucitron.min.js
    ├── tarteaucitron.services.min.js
    ├── advertising.min.js            # Required when integration.adblocker is on
    ├── css/
    │   ├── tarteaucitron.min.css     # Loaded by the library
    │   └── sylius-fix.css            # Plain dark veil instead of the vendor blur (see below)
    └── lang/                         # One .min.js per availableLanguages entry
```

## Installation in Sylius application

```bash
bin/console assets:install
```

Generated URL:

```
/bundles/cyllenedigitalsyliustarteaucitronplugin/tarteaucitron/tarteaucitron.min.js
```

Used in `templates/shop/tarteaucitron.html.twig` via `asset()`.

## Asset versioning

The plugin template appends a content fingerprint to the two URLs it prints itself:

```
…/tarteaucitron/css/sylius-fix.css?v=<12 hex chars>
…/tarteaucitron/tarteaucitron.min.js?v=<12 hex chars>
```

The value comes from `tarteaucitron_asset_version(file)` (first 12 characters of the `xxh128` hash of
`public/tarteaucitron/<file>` in the installed package), so browsers drop their cached copy when a
plugin update changes the file. `assets:install` copies the files without versioning them; this query
string is what busts the cache. tarteaucitron.js strips the query string from its own URL before it
resolves `css/`, `lang/` and the services file, which therefore keep plain URLs.

## Files loaded by plugin / library

| File | Context |
|------|---------|
| `tarteaucitron.min.js` | Shop `<head>` (plugin template) |
| `css/sylius-fix.css` | Shop `<head>` (plugin template) |
| `css/tarteaucitron.min.css` | Loaded by the library |
| `lang/tarteaucitron.<lang>.min.js` | Loaded by the library (`tarteaucitronForceLanguage`) |
| `tarteaucitron.services.min.js` | Loaded by the library after the language file |
| `advertising.min.js` | Loaded by the library when `integration.adblocker` is on |
| `admin/tarteaucitron-admin.css` | Back-office page |

Individual services (gtag, youtube, …) live in `tarteaucitron.services.min.js`, loaded by the main lib; each `job.push` then activates one of them.

`css/tarteaucitron.min.css`, the language file and the services file are only loaded by the library
while `integration.use_external_css` / `integration.use_external_js` are `false` (default); see
[`use_external_css` / `use_external_js`](#use_external_css--use_external_js).

## Upgrading tarteaucitron.js

Never copy files by hand: `bin/update-tarteaucitron.sh` takes the **whole** upstream file set
(a missing `lang/*.min.js` or `advertising.min.js` 404s and the banner never appears), keeps
`css/sylius-fix.css`, and writes `VERSION` plus `SOURCE` (tag, archive URL, archive sha256).

```bash
bin/update-tarteaucitron.sh v1.35.0 b383795e48cc095c8d95dc105639332895e6d4ef859fa37b44b6202b89dac913
```

The second argument is optional: when given, the archive is refused if its sha256 differs. The
script also refuses a tag whose `tarteaucitron.min.js` declares another version, or whose layout
lacks one of the files the library loads. Do not patch vendor files. Then:

```bash
vendor/bin/phpunit --testsuite=unit
```

`VendoredAssetsTest`, `VendorLibraryParityTest` and `VendorInitOptionsParityTest` must stay green:
a new upstream `user.*` key on a built-in service fails `VendorLibraryParityTest` until it is
exposed in the tracker or listed in its `ACKNOWLEDGED_GAPS`; a new `tarteaucitron.init()` option
fails `VendorInitOptionsParityTest` until it is added to `InitOptionCatalog` (back office) or
`IntegrationOptions` (`integration:` configuration), or listed in its `ACKNOWLEDGED_GAPS`. The weekly `upstream.yaml` workflow opens an issue when a new tag is out: see
[maintenance.md](../maintenance.md).

## `css/sylius-fix.css`

Plugin file, kept by `bin/update-tarteaucitron.sh`. The library draws the backdrop of the middle
banner and of the preferences panel with a blurred white veil (`backdrop-filter`) and a 9000px box
shadow; Chrome paints both in tiles over a Sylius shop (grey rectangles, mirrored page edges). The
file replaces them with a plain dark veil and a regular shadow, and makes the panel's click-to-close
backdrop transparent. It does **not** touch stacking: the library already puts the banner above the
veil and under the preferences panel once "Personalize" opens it. While the panel is open, the
banner is hidden (`visibility: hidden`, also out of the accessibility tree) instead of showing through
the veil as a second popup; it comes back if the visitor closes the panel without choosing.

Review it after a major Sylius or tarteaucitron.js upgrade: it targets the library's element ids and
classes.

## `use_external_css` / `use_external_js`

Bundle configuration, `integration:` node — see
[Bundle configuration](configuration.md#use_external_css-default-false). They stop the library from
injecting its own CSS / language and services files; they do **not** switch it to a vendor CDN.

## License

See `public/tarteaucitron/LICENSE`. Plugin MIT license does not replace tarteaucitron license.

## Test-application Encore stubs

`sylius/test-application` always registers `plugin-shop-entry` / `plugin-admin-entry` pointing at
`assets/{shop,admin}/entrypoint.js`. Those files are empty stubs so `yarn build` succeeds; they do
not bundle tarteaucitron.
