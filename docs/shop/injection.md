# Shop injection (front)

## Twig Hook

File: `config/twig_hooks/shop.yaml`

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.base.head':
            tarteaucitron:
                template: '@CylleneDigitalSyliusTarteaucitronPlugin/shop/tarteaucitron.html.twig'
                priority: 100
```

The `tarteaucitron` hook on `sylius_shop.base.head` is part of the [public contract](../architecture/public-contract.md).

## Shop template

`templates/shop/tarteaucitron.html.twig`

```twig
{# Official install: no defer/async on tarteaucitron.min.js. The globals must exist before it loads. #}
{% if tarteaucitron_enabled() %}
{% set tac_nonce = tarteaucitron_script_nonce() %}
{% set tac_texts = tarteaucitron_custom_text() %}
{% set tac_init = tarteaucitron_init() %}
{% set tac_fix_css = asset('bundles/cyllenedigitalsyliustarteaucitronplugin/tarteaucitron/css/sylius-fix.css') %}
{# The library strips the query string from its own URL before resolving lang/ and services. #}
{% set tac_js = asset('bundles/cyllenedigitalsyliustarteaucitronplugin/tarteaucitron/tarteaucitron.min.js') %}
{# The library only requests its lang file, then its services file and stylesheet, once the page
   has loaded: preloading them at the exact URLs it builds saves those round trips. Low priority,
   since nothing needs them before the `load` event. Not with useExternalJs / useExternalCss (the
   theme ships them). #}
{% set tac_dir = (tac_js|split('?')|first)|split('/')|slice(0, -1)|join('/') ~ '/' %}
{% if not (tac_init.useExternalJs ?? false) %}
<link rel="preload" as="script" fetchpriority="low" href="{{ tac_dir }}lang/tarteaucitron.{{ tarteaucitron_language() }}.min.js?v={{ tarteaucitron_library_version() }}"{% if tac_nonce %} nonce="{{ tac_nonce }}"{% endif %}>
<link rel="preload" as="script" fetchpriority="low" href="{{ tac_dir }}tarteaucitron.services.min.js?v={{ tarteaucitron_library_version() }}"{% if tac_nonce %} nonce="{{ tac_nonce }}"{% endif %}>
{% endif %}
{% if not (tac_init.useExternalCss ?? false) %}
<link rel="preload" as="style" fetchpriority="low" href="{{ tac_dir }}css/tarteaucitron.min.css?v={{ tarteaucitron_library_version() }}">
{% endif %}
<link rel="stylesheet" href="{{ tac_fix_css ~ ('?' in tac_fix_css ? '&' : '?') ~ 'v=' ~ tarteaucitron_asset_version('css/sylius-fix.css') }}">
<script{% if tac_nonce %} nonce="{{ tac_nonce }}"{% endif %}>
var tarteaucitronForceLanguage = {{ tarteaucitron_language()|tarteaucitron_json|raw }};
var tarteaucitronForceExpire = {{ tarteaucitron_consent_lifetime_days()|tarteaucitron_json|raw }};
{% if tac_texts is not empty %}
var tarteaucitronCustomText = {{ tac_texts|tarteaucitron_json|raw }};
{% endif %}
</script>
<script{% if tac_nonce %} nonce="{{ tac_nonce }}"{% endif %} src="{{ tac_js ~ ('?' in tac_js ? '&' : '?') ~ 'v=' ~ tarteaucitron_asset_version('tarteaucitron.min.js') }}"></script>
<script{% if tac_nonce %} nonce="{{ tac_nonce }}"{% endif %} type="text/javascript">
tarteaucitron.init({{ tac_init|tarteaucitron_json|raw }});
{# Without any service the library reads tarteaucitron.job.length before creating it when its panel
   is built late (adblocker detection): the error stops it before it wires its buttons. #}
tarteaucitron.job = tarteaucitron.job || [];
{{ tarteaucitron_tracker_scripts()|raw }}
</script>
{% endif %}
```

The three `<link rel="preload" fetchpriority="low">` fetch the library's lang file, services file
and stylesheet ahead of time: the library only requests them once the page has loaded, one after
the other. Their URLs must stay byte-identical to the ones the library builds (its directory,
`.min`, and `?v=<library version>`), or the browser downloads each file twice. The two script
preloads are skipped with `integration.use_external_js`, the stylesheet one with
`integration.use_external_css`, where the theme ships those files.

`tarteaucitron.job` is created even when no service is enabled: otherwise, with the ad-blocker
detection on, the library fails on it and never wires its icon and panel buttons. Keep that line in
an overridden template.

The globals are set **before** the library script, which reads them once at load:
`tarteaucitronForceLanguage` so tarteaucitron.js does not request a language file from
`<html lang>` and 404, `tarteaucitronForceExpire` for the consent cookie lifetime, and
`tarteaucitronCustomText` (only when the admin set texts for the current locale). Do not add `defer` on `tarteaucitron.min.js`:
the official snippet calls `init()` immediately after. Encode every value with the
`|tarteaucitron_json` filter, not Twig `json_encode`.

The three `<script>` tags and the two script preloads carry `nonce` when a nonce is configured (per response with
`script_nonce_provider`, or fixed with `script_nonce`, see
[Bundle configuration](../integration/configuration.md#script_nonce--script_nonce_provider)). The two plugin asset URLs carry
a `?v=` content fingerprint ([Asset versioning](../integration/assets.md#asset-versioning)).

## Loaded assets

| Asset | Role |
|-------|------|
| `tarteaucitron.min.js` | Vendored library (see VERSION) |
| `css/sylius-fix.css` | Plain dark veil instead of the vendor blur, banner hidden while the preferences panel is open ([details](../integration/assets.md#csssylius-fixcss)) |
| `lang/tarteaucitron.<lang>.min.js` | Loaded by the library (`tarteaucitronForceLanguage`), unless `integration.use_external_js` |
| `tarteaucitron.services.min.js` | Loaded by the library after the language file, unless `integration.use_external_js` |
| `advertising.min.js` | Loaded by the library when `integration.adblocker` is on |
| `css/tarteaucitron.min.css` | Loaded by the library, unless `integration.use_external_css` |

All language files listed in `availableLanguages` inside `tarteaucitron.min.js` are vendored.
`tarteaucitron_language()` maps `LocaleContextInterface` onto that list and falls back to `en`.

## Display condition

`tarteaucitron_enabled()` returns `false` if:

- No shop channel resolved
- No configuration in DB for this channel
- `enabled === false` on configuration

**Important:** an entity created in back office but never saved does not exist in DB → shop off.

## Configuration resolution

```
ChannelContext (Sylius)
  → ConsentConfigurationProvider::resolve()
  → ResolvedConsent { enabled, init, services, consentLifetimeDays, localizedOptions }
  → TarteaucitronRuntime (+ TarteaucitronLanguageResolver for the current locale)
```

Per-request cache in provider (`ResetInterface`). The provider and `ResolvedConsent` are internal.

## Hook priority 100

High enough to run early in `<head>`, before third-party theme scripts - consent must precede tracker loading.

Themes can add other hooks on `sylius_shop.base.head` with lower or higher priority as needed.

## Theme customization

To override the template:

```twig
{# templates/bundles/CylleneDigitalSyliusTarteaucitronPlugin/shop/tarteaucitron.html.twig #}
```

Start from a copy of the plugin's template and keep `tarteaucitron.init()` and the plugin Twig
functions unless assuming full replacement. The functions are public API; the template itself is not,
so re-check your copy after each plugin upgrade. A typical reason to override it is a per-request CSP
nonce: [Bundle configuration](../integration/configuration.md#script_nonce--script_nonce_provider).

The hookable can also be moved or disabled through `sylius_twig_hooks` in the application:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.base.head':
            tarteaucitron:
                enabled: false
```

## Diagram

See [Shop sequence](../diagrams/shop-render-sequence.md).
