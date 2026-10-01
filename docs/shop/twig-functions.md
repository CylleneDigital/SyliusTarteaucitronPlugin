# Twig functions

Extension: `src/Twig/TarteaucitronExtension.php`

Lazy implementations via `[RuntimeClass::class, 'method']` callables (Twig runtimes).

## Shop functions (`TarteaucitronRuntime`)

Usable in shop theme templates. Their names, arguments and kind of returned value are **public API**
(see [Public contract](../architecture/public-contract.md)); the extension and runtime classes
behind them are internal.

### `tarteaucitron_enabled(): bool`

Whether tarteaucitron should be injected for the current channel.

```twig
{% if tarteaucitron_enabled() %}
    {# consent banner or active scripts #}
{% endif %}
```

### `tarteaucitron_init(): array`

Returns the official `tarteaucitron.init()` object (vendor jsKeys).

Typical usage in plugin template (must use `|tarteaucitron_json`, not Twig `json_encode`):

```twig
tarteaucitron.init({{ tarteaucitron_init()|tarteaucitron_json|raw }});
```

### `tarteaucitron_language(): string`

tarteaucitron.js language code for the current shop locale (`es`, `de`, `en`, …),
resolved via Sylius `LocaleContextInterface` then projected onto the vendor list
(with configurable aliases, default `nb` → `no`).
Unknown / missing locales fall back to `en`. Used to set `tarteaucitronForceLanguage`
**before** loading `tarteaucitron.min.js`.

```twig
<script>var tarteaucitronForceLanguage = {{ tarteaucitron_language()|tarteaucitron_json|raw }};</script>
```

### `tarteaucitron_consent_lifetime_days(): int`

How long the visitor's choice is kept, in days (back office "Essentials" tab, default 180, at most
364). Emitted as `tarteaucitronForceExpire` before loading `tarteaucitron.min.js`: without it the
library keeps the choice one year, and it ignores values of 365 days or more.

```twig
<script>var tarteaucitronForceExpire = {{ tarteaucitron_consent_lifetime_days()|tarteaucitron_json|raw }};</script>
```

### `tarteaucitron_custom_text(): array`

Banner texts the admin set for the **current locale** (tab "Texts and languages"), keyed by
`tarteaucitron.lang` key (`acceptAll`, `alertBigPrivacy`, …). Empty when none: the library texts
apply. Emitted as `tarteaucitronCustomText`, which the library merges over its language file.

`tarteaucitron_init()` likewise replaces `privacyUrl` / `readmoreLink` with the current locale
links when set.

### `tarteaucitron_asset_version(file): string`

12-character fingerprint of a file under `public/tarteaucitron/` (`'css/sylius-fix.css'`,
`'tarteaucitron.min.js'`), computed once per process. Asset URLs are not versioned, so the shop
template appends it as `?v=` to make browsers drop their cached copy when a plugin update changes the
file. tarteaucitron.js strips the query string from its own URL before resolving `lang/` and
`tarteaucitron.services.min.js`; those files, loaded by the library, stay unversioned. Refuses paths outside
that directory.

```twig
{% set tac_fix_css = asset('bundles/cyllenedigitalsyliustarteaucitronplugin/tarteaucitron/css/sylius-fix.css') %}
<link rel="stylesheet" href="{{ tac_fix_css ~ ('?' in tac_fix_css ? '&' : '?') ~ 'v=' ~ tarteaucitron_asset_version('css/sylius-fix.css') }}">
```

### `tarteaucitron_script_nonce(): ?string`

CSP nonce for the plugin's own `<script>` tags, asked on every call to the configured
`ScriptNonceProviderInterface` (`script_nonce_provider`, or the fixed `script_nonce`).
`null` (default, or an empty string) emits the official snippet without a nonce attribute.
Vendor-injected files are not covered.

Returns `null` when no nonce is configured: the tags then carry no `nonce` attribute. See
[Bundle configuration](../integration/configuration.md#script_nonce--script_nonce_provider).

### `tarteaucitron_json` (filter)

A Twig **filter**, not a function: `value|tarteaucitron_json`. `json_encode` with the `InlineJson`
flags, the same as tracker snippets: `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS`
(plus `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR`). `<`, `>`, `&`, `"`
and `'` are escaped, so the output can neither close a `<script>` element nor an HTML attribute.
Print it with `|raw` inside a `<script>`.

### `tarteaucitron_tracker_scripts(): string`

JS snippets (`tarteaucitron.user.*` assignments + `job.push`). May be empty.

Always used with `|raw` in plugin template — safe because every value goes through `InlineJson`.

### `tarteaucitron_is_embed(string $type): bool`

Whether a tracker is an embed type (YouTube, Maps, etc.) **and** known to the registry.

```twig
{% if tarteaucitron_is_embed('youtube') %}
    {# show tarteaucitron placeholder #}
{% endif %}
```

Does **not** check if the service is **enabled** in back office — combine with your own logic if
needed. The enabled state is not exposed by a public shop function.

## Admin functions (`TarteaucitronAdminRuntime`)

**Internal**: reserved for the plugin's own back-office template, they can change in a minor
version. Do not call them from application templates.

| Function | Return | Usage |
|----------|--------|-------|
| `tarteaucitron_init_tabs()` | list tabs + cards + options | Init form tabs |
| `tarteaucitron_compliance_findings(form)` | field → message map | CNIL guidance warnings |
| `tarteaucitron_group_services_by_category(services)` | grouped accordion | Services panel |
| `tarteaucitron_service_enabled_map(services)` | type → bool map | Consent mode badges |
| `tarteaucitron_trackers_by_consent_mode(mode)` | list of types | Linked services |
| `tarteaucitron_consent_alert(key, map)` | alert or null | Back-office warnings |
| `tarteaucitron_service_admin_meta(type, enabled, parameters)` | label, hint, badge, css, incomplete | Service metadata |

## Underlying DI services (internal)

| Interface / class | Service ID |
|--------------------|------------|
| `ConsentConfigurationProviderInterface` | `cyllene_digital_sylius_tarteaucitron.provider.consent` |
| `TrackerRegistryInterface` | `cyllene_digital_sylius_tarteaucitron.tracker.registry` |
| `TrackerScriptRenderer` | `cyllene_digital_sylius_tarteaucitron.tracker.script_renderer` |
| `TarteaucitronLanguageResolver` | `cyllene_digital_sylius_tarteaucitron.locale.language_resolver` |

These services, `ResolvedConsent` and the provider are `@internal`: injecting them in application
code works, but is not covered by semver. From PHP, prefer rendering the public Twig functions; if you
do depend on the provider, pin the plugin's minor version and re-check on upgrade.
