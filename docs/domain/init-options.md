# `tarteaucitron.init()` options

## Data model

These classes form the init chain (all internal):

| Class | File | Role |
|-------|------|------|
| `InitOption` | `src/Consent/Init/InitOption.php` | Single option metadata (key, jsKey, type, default, section, choices, `omitIfEmpty`, `url`, `relatedConsentMode`, `pattern`) and normalization |
| `InitOptionCatalog` | `src/Consent/Init/InitOptionCatalog.php` | Instance catalog of all supported options (DI singleton) |
| `InitOptions` | `src/Consent/Init/InitOptions.php` | Normalized values (raw + defaults merge) |
| `InitOptionsMapper` | `src/Consent/Init/InitOptionsMapper.php` | Conversion to JS payload (jsKeys) |
| `IntegrationOptions` | `src/Consent/Init/IntegrationOptions.php` | Theme-level options from the `integration:` bundle configuration, turned into jsKeys |

## Dual naming

- **`key`** (snake_case) — JSON storage in DB, back-office form field names
- **`jsKey`** — key passed to `tarteaucitron.init()` in the browser

Example: `deny_all_cta` → `DenyAllCta`.

## Back-office sections

| Enum `InitOptionSection` | Content |
|--------------------------|---------|
| `Essential` | Privacy / read-more links (tab "Essentials", with the enable switch and the consent lifetime) |
| `Compliance` | High privacy, accept / deny buttons, default state, close popup, mandatory cookies, DNT (first card of "Compliance") |
| `ConsentMode` | Google / Bing / Piano / Piwik PRO / soft consent mode (second card of "Compliance") |
| `Display` | Orientation, icon, lists, credit (tab "Appearance") |
| `Advanced` | Cookie name / domain, dataLayer event (tab "Advanced") |

The "Texts and languages" tab holds no catalog option: it edits `localized_options` (see
[below](#per-locale-links-and-texts-localizedoptions)).

Theme-level options (`useExternalCss`, `mandatoryCta`, `useExternalJs`, `serverSide`, `adblocker`,
`hashtag`, `customCloserId`) are not in the catalog: they come from the `integration:` bundle configuration
(`IntegrationOptions::JS_KEYS`) and are merged into the payload for every channel, after the
back-office options (an empty value is left out, so the library default applies) — see
[Bundle configuration](../integration/configuration.md#integration).

Sections are layout only (never stored): an option can move between sections in a minor version.
Every option gets its label and help from its key: `ui.<key>` and `ui.<key>_help`
(`AdminTranslationsTest` fails when one is missing).

## Full catalog

In `InitOptionCatalog::build()` order.

| key | jsKey | Type | Default | Section | Flags |
|-----|-------|------|---------|---------|-------|
| `privacy_url` | `privacyUrl` | string | `''` | Essential | `url` |
| `readmore_link` | `readmoreLink` | string | `''` | Essential | `url` |
| `high_privacy` | `highPrivacy` | bool | `true` | Compliance | |
| `accept_all_cta` | `AcceptAllCta` | bool | `true` | Compliance | |
| `deny_all_cta` | `DenyAllCta` | bool | `true` | Compliance | |
| `service_default_state` | `serviceDefaultState` | choice | `'wait'` | Compliance | |
| `close_popup` | `closePopup` | bool | `true` | Compliance | |
| `always_need_consent` | `alwaysNeedConsent` | bool | `false` | Compliance | |
| `mandatory` | `mandatory` | bool | `true` | Compliance | |
| `handle_browser_dnt_request` | `handleBrowserDNTRequest` | bool | `false` | Compliance | |
| `google_consent_mode` | `googleConsentMode` | bool | `true` | ConsentMode | `relatedConsentMode: Google` |
| `bing_consent_mode` | `bingConsentMode` | bool | `true` | ConsentMode | `relatedConsentMode: Bing` |
| `piano_consent_mode` | `pianoConsentMode` | bool | `true` | ConsentMode | |
| `piano_consent_mode_essential` | `pianoConsentModeEssential` | bool | `false` | ConsentMode | |
| `piwik_consent_mode` | `piwikConsentMode` | bool | `true` | ConsentMode | |
| `soft_consent_mode` | `softConsentMode` | bool | `false` | ConsentMode | |
| `orientation` | `orientation` | choice | `'middle'` | Display | |
| `body_position` | `bodyPosition` | choice | `'top'` | Display | |
| `show_alert_small` | `showAlertSmall` | bool | `false` | Display | |
| `show_title_banner` | `showTitleBanner` | bool | `false` | Display | |
| `show_icon` | `showIcon` | bool | `true` | Display | |
| `icon_position` | `iconPosition` | choice | `'BottomRight'` | Display | |
| `icon_src` | `iconSrc` | string | `''` | Display | `omitIfEmpty`, `url` (raster `data:` URI allowed) |
| `group_services` | `groupServices` | bool | `true` | Display | |
| `show_details_on_click` | `showDetailsOnClick` | bool | `true` | Display | |
| `partners_list` | `partnersList` | bool | `true` | Display | |
| `more_info_link` | `moreInfoLink` | bool | `true` | Display | |
| `cookies_list` | `cookieslist` | bool | `false` | Display | |
| `cookieslist_embed` | `cookieslistEmbed` | bool | `false` | Display | |
| `remove_credit` | `removeCredit` | bool | `false` | Display | |
| `cookie_name` | `cookieName` | string | `'tarteaucitron'` | Advanced | `pattern` (RFC 6265 cookie-name token) |
| `cookie_domain` | `cookieDomain` | string | `''` | Advanced | `omitIfEmpty`, `pattern` (host name, optional leading dot) |
| `data_layer` | `dataLayer` | bool | `false` | Advanced | |

Flags:

- `omitIfEmpty` — left out of the `init()` payload when empty, so the library default applies;
- `url` — validated as a link (see [Normalization](#normalization-initoptionsfromarray));
- `relatedConsentMode` — the back office lists the related services and shows the
  [consent mode alert](consent-alerts.md);
- `pattern` — PCRE the value must match, in the form (`Regex`, plus `NotBlank` when the default is
  not empty) and on read. The exact expressions are in `InitOptionCatalog` and in
  [Forms](../admin/forms.md#validation).

### Choices

| key | Allowed values |
|-----|----------------|
| `body_position` | `top`, `bottom` |
| `orientation` | `top`, `middle`, `bottom`, `popup` |
| `icon_position` | `BottomRight`, `BottomLeft`, `TopRight`, `TopLeft` |
| `service_default_state` | `true`, `wait`, `false` |

## Library limits (tarteaucitron.js 1.35.0)

Options the library accepts but ignores in some setups. The back office says so next to the field
(`OptionConflicts`, an info box, not a CNIL warning); re-check this list after each library upgrade.

| Option | Ignored when | Why |
|--------|--------------|-----|
| `groupServices` | `integration.adblocker: true` | With the ad-blocker detection the panel is built once `advertising.min.js` has loaded, after the grouping code ran and found no category. Services stay listed without groups. |
| `showAlertSmall` | `showIcon` is on too | Both buttons get `id="tarteaucitronManager"`; the library wires the first one, the icon. The "Manage services" button shows but does nothing. It only appears once the visitor has chosen. |
| `mandatoryCta` | the library stylesheet is used | `tarteaucitron.css` hides the buttons (`display: none !important`). Hence an `integration:` key, for a theme stylesheet (`use_external_css`). |

With no service enabled and the ad-blocker detection on, the library also read
`tarteaucitron.job.length` before creating `tarteaucitron.job`; the error stopped it before it wired
any button (icon, panel). `templates/shop/tarteaucitron.html.twig` creates the array after `init()`.

## Normalization (`InitOptions::fromArray`)

1. For each catalog option, read raw value if present
2. Otherwise use `InitOption::default`
3. Apply `InitOption::normalize()` (bool, string, choice)
4. Unknown keys in raw JSON are **ignored**
5. Options with a `pattern` (`cookie_name`, `cookie_domain`): a value that does not match falls
   back to the default
6. Options with `url: true` (`privacy_url`, `readmore_link`, `icon_src`) keep only
   `InitOption::isSafeLink()` values: an `http` / `https` URL or a relative path starting with a
   single `/`, with no quote, `<`, `>`, backtick, backslash, whitespace or control character
   (`UNSAFE_LINK_CHARACTERS`). Anything else — `javascript:`, `data:`, protocol-relative `//host` —
   becomes `''`. `icon_src` also accepts a raster base64 data URI (`ALLOWED_ICON_DATA_URI`:
   `data:image/png|jpeg|jpg|gif|webp;base64,…`; SVG is refused because it can carry script)

Upgrade-safe behavior: adding a catalog option does not break existing configs (default applied).

## Runtime mapping (`InitOptionsMapper`)

```php
public function toTarteaucitronInit(InitOptions $options): array
```

- Iterates all catalog options
- Omits keys with `omitIfEmpty` when value is empty/null
- Produces associative array `[jsKey => value]`

The consent provider then adds the `integration:` options (`IntegrationOptions::toInit()`), and the
`tarteaucitron_init()` Twig function replaces `privacyUrl` / `readmoreLink` with the current
locale's links when the admin set them.

## Seeding a new configuration

`CatalogConsentDefaults::defaultInitOptions()` returns `InitOptions::defaults($catalog)->toArray()` to initialize a new back-office entity.

## Source of truth

Any catalog change belongs in `InitOptionCatalog::build()`. Update this document when the catalog changes.
Step by step: [Adding an init option](../development/adding-an-init-option.md).

## Per-locale links and texts (`LocalizedOptions`)

Not init options: stored apart in `localized_options`, `{localeCode: {key: value}}`, edited in the
"Texts and languages" tab (one block per **channel locale**, default locale first).

| Key | Goes to | Empty value keeps |
|---|---|---|
| `privacy_url`, `readmore_link` | `privacyUrl` / `readmoreLink` in the `init()` payload | the channel-wide link ("Essentials") |
| `middle_bar_head`, `alert_big_privacy`, `accept_all`, `deny_all`, `personalize`, `close`, `disclaimer`, `mandatory_text` | `tarteaucitronCustomText` (`tarteaucitron.lang` keys) | the library text of that language, shown as placeholder (`VendorLanguageTexts`) |

tarteaucitron.js concatenates its texts into HTML and double-quoted attributes: `<`, `>` and `"`
are refused by the form **and** dropped by `LocalizedOptions::normalize()` on read, like unsafe
links, so a value written straight into the database cannot reach the shop. Values set for a locale
later removed from the channel are kept on save.

The curated text list stays short on purpose; `alertBigClick` / `alertBig` (implied-consent
banner) are not exposed.
