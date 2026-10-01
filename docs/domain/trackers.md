# Trackers (tarteaucitron services)

A **tracker** corresponds to an entry in the `tarteaucitron.job` array — a service managed by tarteaucitron (gtag, youtube, etc.).

## Architecture

```
TrackerDefinitionInterface                (public)
    └── AbstractTrackerDefinition         (public, recommended base class)
            ├── src/Tracker/{Category}/*Tracker.php   (built-in)
            ├── ConfiguredTracker                     (one per `trackers:` YAML entry)
            └── application classes                   (custom)

TrackerRegistry (internal)  ←── tagged_iterator cyllene_digital_sylius_tarteaucitron.tracker
    │
    ├── prototype config/services/trackers.php
    ├── `trackers:` entries (registered by the extension)
    └── application services (same tag, autoconfigured)
```

## `TrackerDefinitionInterface`

| Method | Description |
|--------|-------------|
| `getType()` | tarteaucitron job key (e.g. `gtag`) |
| `getCategory()` | `TrackerCategory` enum (analytic, ads, api, video, social, …) |
| `getLabel()` | Admin translation key (default `cyllene_digital_sylius_tarteaucitron.ui.service_{type}`) |
| `getParameters()` | List of `TrackerParameter` (BO key → `tarteaucitron.user.*` key, `required`, `placeholder`, `label`) |
| `isEmbed()` | HTML placeholder-based service (YouTube, Maps…) |
| `isSeededByDefault()` | Create a DB row on first back-office access |
| `areRequiredParametersSatisfied()` | Can the JS snippet be emitted? |
| `getConsentMode()` | Google / Bing / GTM link (nullable) |
| `getAdminHintTranslationKey()` | Optional back-office hint |

Defaults for everything but `getType()` and `getCategory()` live in `AbstractTrackerDefinition`. New
methods may be added to the interface in a minor version, with a default in the abstract class.

## Categories and back-office display order

Fixed order in `TrackerRegistry::CATEGORY_ORDER`:

`analytic` → `ads` → `api` → `video` → `social` → `support` → `comment` → `other`

## Consent modes

Enum `ConsentMode`: `Google`, `Bing`, `Gtm`.

Linked trackers:

| Mode | Trackers |
|------|----------|
| Google | `gtag`, `googleads` |
| Bing | `clarity`, `bingads` |
| Gtm | `googletagmanager` |

## Built-in catalog (46 trackers)

Parameters marked **(opt.)** are `required: false`: the service is emitted without them.

| type | Category | Embed | Consent | BO parameters → user key |
|------|----------|-------|---------|--------------------------|
| `gtag` | analytic | no | Google | `gtag_ua` → `gtagUa`, `gtag_custom_domain` → `gtagCustomDomain` (opt.) |
| `facebookpixel` | ads | no | — | `facebookpixel_id` → `facebookpixelId` |
| `clarity` | analytic | no | Bing | `clarity_id` → `clarity` |
| `hotjar` | analytic | no | — | `hotjar_id` → `hotjarId`, `hotjar_sv` → `HotjarSv` |
| `linkedininsighttag` | ads | no | — | `linkedininsighttag_id` → `linkedininsighttag` |
| `googletagmanager` | api | no | Gtm | `googletagmanager_id` → `googletagmanagerId`, `googletagmanager_custom_domain` → `googletagmanagerCustomDomain` (opt.) |
| `googleads` | ads | no | Google | `googleads_id` → `googleadsId`, `googleads_custom_domain` → `googleadsCustomDomain` (opt.) |
| `bingads` | ads | no | Bing | `bingads_id` → `bingadsID` |
| `pinterestpixel` | ads | no | — | `pinterestpixel_id` → `pinterestpixelId` |
| `tiktok` | analytic | no | — | `tiktok_id` → `tiktokId` |
| `hubspot` | analytic | no | — | `hubspot_id` → `hubspotId`, `hubspot_business_unit_id` → `hubspotBusinessUnitId` (opt.) |
| `matomo` | analytic | no | — | `matomo_id` → `matomoId`, `matomo_host` → `matomoHost` |
| `snapchat` | analytic | no | — | `snapchat_id` → `snapchatId`, `snapchat_email` → `snapchatEmail` (opt.) |
| `twitteruwt` | analytic | no | — | `twitteruwt_id` → `twitteruwtId` |
| `adsense` | ads | **yes** | — | — |
| `adsenseauto` | ads | no | — | `adsense_ca_pub` → `adsensecapub` |
| `amazon` | ads | **yes** | — | — |
| `googlefonts` | api | no | — | `google_fonts` → `googleFonts` |
| `recaptcha` | api | **yes** | — | `recaptcha_api` → `recaptchaapi` (opt.), `recaptcha_hl` → `recaptcha_hl` (opt.) |
| `googlemapsembed` | api | **yes** | — | — |
| `youtube` | video | **yes** | — | — |
| `youtubeplaylist` | video | **yes** | — | — |
| `tiktokvideo` | video | **yes** | — | — |
| `facebook` | social | **yes** | — | — |
| `facebookpost` | social | **yes** | — | — |
| `instagram` | social | **yes** | — | — |
| `twitter` | social | **yes** | — | — |
| `twitterembed` | social | **yes** | — | — |
| `linkedin` | social | **yes** | — | — |
| `pinterest` | social | **yes** | — | — |
| `criteoonetag` | ads | no | — | `criteoonetag_account` → `criteoonetagAccount` |
| `klaviyo` | ads | no | — | `klaviyo_company_id` → `klaviyoCompanyId` |
| `kwanko` | ads | **yes** | — | — |
| `crisp` | support | no | — | `crisp_id` → `crispID` |
| `tawkto` | support | no | — | `tawkto_id` → `tawktoId`, `tawkto_widget_id` → `tawktoWidgetId` (opt.) |
| `trustpilot` | other | **yes** | — | — |
| `sendinblue` | other | no | — | `sendinblue_key` → `sendinblueKey` |
| `calendly` | other | **yes** | — | — |
| `abtasty` | api | no | — | `abtasty_id` → `abtastyID` |
| `kameleoon` | analytic | no | — | `kameleoon` → `kameleoon` |
| `contentsquare` | analytic | no | — | `contentsquare_id` → `contentsquareID` |
| `plausible` | analytic | no | — | `plausible_domain` → `plausibleDomain`, `plausible_endpoint` → `plausibleEndpoint` (opt.) |
| `piwikpro` | analytic | no | — | `piwik_pro_id` → `piwikProId`, `piwik_pro_container` → `piwikProContainer` |
| `atinternet` | analytic | no | — | `at_lib_url` → `atLibUrl` |
| `vimeo` | video | **yes** | — | — |
| `dailymotion` | video | **yes** | — | — |

The optional `*_custom_domain` parameters of `gtag`, `googleads` and `googletagmanager` set the
host the Google script is loaded from (first-party / server-side tagging domain); empty, the library
uses `www.googletagmanager.com`.

`criteoonetag` is Criteo's retargeting tag; the vendor `criteo` job only renders ad zones
(`criteo-canvas` placeholders) and is not built in.

## Per-channel persistence

`TarteaucitronService` entity:

- `type` — job key (unique index per configuration)
- `enabled` — bool
- `parameters` — JSON `{ "gtag_ua": "G-XXX", ... }`

## Catalog → entity synchronization

`ServiceCatalogSynchronizer::ensureSeeded()`:

- For each tracker with `isSeededByDefault() === true`
- If missing from collection → creates `TarteaucitronService` (disabled, empty parameters), so the
  service appears in the back office, switched off
- Never removes existing services

Called on creation (`TarteaucitronConfigurationFactory`) and on every back-office open of an existing config.

## Registry rules

- Duplicate job key → the container build fails (`UniqueTrackerTypePass`, for `trackers:` entries
  and classes constructible without arguments, so every built-in). A duplicate the pass cannot see
  (a custom class with constructor arguments) still makes `TrackerRegistry` throw
  `InvalidArgumentException` at runtime.
- `getOrNull()` for tolerant lookup (shop runtime, embed check)
- `get()` throws if type unknown

## Custom extension

See [Custom tracker](../integration/custom-tracker.md) and [Adding a tracker](../development/adding-a-tracker.md).

## Source of truth

One class per job key under `src/Tracker/`. Do not add a central catalog. Keep this document's built-in table in sync when adding a service.
