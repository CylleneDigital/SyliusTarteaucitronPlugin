# Public contract and versioning

This plugin follows [semantic versioning](https://semver.org). The elements below must **not** be
changed without a **major** version bump. Everything else is `@internal` and may change in a minor
version (see [What is internal](#what-is-internal)). [Support policy](#support-policy) and
[upgrades](#upgrading) are at the end of the page.

## PHP API

| Element | Namespace `CylleneDigital\SyliusTarteaucitronPlugin\…` | Role |
|---------|------|------|
| `AbstractTrackerDefinition` | `Consent\Tracker` | **Recommended extension point** for custom trackers |
| `TrackerDefinitionInterface` | `Consent\Tracker` | Contract the registry consumes |
| `TrackerParameter` | `Consent\Tracker` | One back-office field ↔ one `tarteaucitron.user.*` key |
| `TrackerCategory` | `Consent\Tracker` | Category enum (string values) |
| `ConsentMode` | `Consent\Tracker` | Consent mode enum (Google, Bing, GTM) |
| `ScriptNonceProviderInterface` | `Csp` | Per-response CSP nonce for the plugin's script tags (`script_nonce_provider`) |
| `CylleneDigitalSyliusTarteaucitronPlugin` | root | Bundle class, registered in `config/bundles.php` |

**Extend `AbstractTrackerDefinition`.** A new method may be added to `TrackerDefinitionInterface` in a
**minor** version, always with a default implementation in `AbstractTrackerDefinition`: a class
extending it keeps working. A class implementing the interface directly does so at its own risk and
may need the new method after a minor upgrade.

See [Custom tracker](../integration/custom-tracker.md).

## Tracker DI tag

```
cyllene_digital_sylius_tarteaucitron.tracker
```

Collected by `UniqueTrackerTypePass` for the `TrackerRegistry`: a locator keyed by job key, plus an
iterable for trackers whose job key is only known once built. Any service implementing
`TrackerDefinitionInterface` receives it through autoconfiguration; without autoconfiguration, tag
the service by hand.

## Bundle configuration keys

Written by integrators under `cyllene_digital_sylius_tarteaucitron:` in application YAML:

| Key | Default |
|-----|---------|
| `script_nonce` | `null` (fixed nonce) |
| `script_nonce_provider` | `null` (`nelmio`, or the id of a `ScriptNonceProviderInterface` service) |
| `locale_aliases` | `{ nb: no }` |
| `integration` | `use_external_css: false`, `mandatory_cta: false`, `use_external_js: false`, `server_side: false`, `adblocker: false`, `hashtag: '#tarteaucitron'`, `custom_closer_id: null` |
| `trackers` | none - `{jobKey: {category, label, embed, parameters: {userKey: {required, placeholder, label}}}}` |

Renaming or removing a key, or changing a default in a way that changes behaviour, is a major.
Reference: [Bundle configuration](../integration/configuration.md).

## Shop Twig Hook

| Element | Value |
|---------|-------|
| Target hook | `sylius_shop.base.head` |
| Hookable name | `tarteaucitron` |
| Template | `@CylleneDigitalSyliusTarteaucitronPlugin/shop/tarteaucitron.html.twig` |
| Priority | `100` |

Renaming or moving this hook breaks themes and integrations that rely on it.

## Shop Twig functions and filter

Functions:

- `tarteaucitron_enabled()`
- `tarteaucitron_init()`
- `tarteaucitron_language()`
- `tarteaucitron_consent_lifetime_days()`
- `tarteaucitron_custom_text()`
- `tarteaucitron_asset_version(file)`
- `tarteaucitron_library_version()`
- `tarteaucitron_script_nonce()`
- `tarteaucitron_tracker_scripts()`
- `tarteaucitron_is_embed(type)`

Filter:

- `tarteaucitron_json`

The name, arguments and kind of returned value are stable. The admin functions
(`tarteaucitron_init_tabs()`, `tarteaucitron_compliance_findings()`, …) are **not**: they serve the
plugin's own back-office template. Details: [Twig functions](../shop/twig-functions.md).

## Admin hooks and route

| Element | Value |
|---------|-------|
| Admin Twig hooks | `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.*` |
| Route name | `cyllene_digital_sylius_tarteaucitron_admin_configuration` |

An application may add, move or disable hookables under these hooks and link to the route. The
templates the plugin's own hookables render are internal. See [Admin Twig Hooks](../admin/twig-hooks-admin.md).

## Database tables

- `cyllene_tarteaucitron_configuration`
- `cyllene_tarteaucitron_service` (the `type` column holds tarteaucitron job keys)

Schema changes require a documented migration in [UPGRADE.md](../../UPGRADE.md).

## JSON `init_options` keys (snake_case)

Stored in the `init_options` column. Examples:

- `privacy_url`, `high_privacy`, `deny_all_cta`
- `google_consent_mode`, `cookies_list`

Full list: [Init options](../domain/init-options.md). Renaming a key breaks existing configurations
and integrations reading the database.

## JSON `localized_options` keys

Stored per locale code in the `localized_options` column, `{localeCode: {key: value}}`:
`privacy_url`, `readmore_link`, `middle_bar_head`, `alert_big_privacy`, `accept_all`, `deny_all`,
`personalize`, `close`, `disclaimer`, `mandatory_text`. Same rule as `init_options`.

## Vendor JavaScript keys (jsKey)

The mapper produces official tarteaucitron keys, including irregularities documented by the vendor:

| snake_case (storage) | jsKey (JS) |
|----------------------|------------|
| `deny_all_cta` | `DenyAllCta` |
| `accept_all_cta` | `AcceptAllCta` |
| `cookies_list` | `cookieslist` |
| `handle_browser_dnt_request` | `handleBrowserDNTRequest` |

Before renaming a `jsKey`, check the [official tarteaucitron documentation](https://tarteaucitron.io/en/free-installation-open-source/).

## Translation key conventions

| Key | Used for |
|-----|----------|
| `cyllene_digital_sylius_tarteaucitron.ui.service_{type}` | Back-office name of a tracker (default `getLabel()`) |
| `cyllene_digital_sylius_tarteaucitron.ui.{parameter key}` | Label of a tracker parameter, unless `TrackerParameter::$label` is set |

Custom trackers rely on them: the application adds these keys to its own translations.

## What is internal

Everything not listed above is internal, in particular (most of these classes carry `@internal`):

- `InitOption`, `InitOptionCatalog`, `InitOptions`, `InitOptionsMapper`, `InitOptionSection`,
  `InitOptionType`, `IntegrationOptions`;
- `TrackerRegistry`, `TrackerRuntimeState`, `TrackerScriptRenderer`, `ConfiguredTracker`,
  `VendorServiceCatalog`;
- `ConsentConfigurationProvider`, `ConsentConfigurationProviderInterface`, `ResolvedConsent`;
- `ComplianceCheck`, `ConsentAlert`, `InlineJson`, `LocalizedOptions`, `VendorLanguageTexts`;
- entities, repository, factory, synchronizer, `AdminChannelResolver`, `TarteaucitronLanguageResolver`;
- form types, data mappers, `LinkConstraints`, controller, menu listener;
- Twig extension and runtimes, admin Twig functions, the plugin templates;
- DI extension, `Configuration` class, compiler pass, service ids;
- built-in tracker classes under `src/Tracker/` and `TrackerAdminHints` (their job keys are stored
  data, their class names are not API).

Decorating or replacing an internal service (for example the consent provider) works, but is not
covered by semver.

## Support policy

| Line | What is covered |
|---|---|
| Latest minor of the current major | Bug and security fixes |
| Previous major | Security only, for 12 months after the next major is released |
| Sylius / PHP / Symfony versions | Those of the continuous integration matrix; support ends when Sylius itself drops a version |

Report security flaws privately - see [SECURITY.md](../../SECURITY.md).

## Upgrading

Every compatibility break is written in [UPGRADE.md](../../UPGRADE.md) before it is released, with
the exact steps to carry out.
