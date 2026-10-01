# Adding a `tarteaucitron.init()` option

Every option the vendored tarteaucitron.js reads through `tarteaucitron.parameters.*` is exposed
either in the back office (`InitOptionCatalog`) or in the `integration:` bundle configuration
(`IntegrationOptions`). `VendorInitOptionsParityTest` enforces it: after a library bump that
introduces a new option, the unit suite fails until the option is added to one of them (or
deliberately listed as a gap).

## Back office or `integration:`?

| The option… | Goes to |
|---|---|
| is a consent / display choice a shop admin makes per channel | `InitOptionCatalog` — this checklist |
| belongs to the theme integration: a wrong value breaks the banner or a theme link, or it needs files the theme ships (`useExternalCss`, `mandatoryCta`, `useExternalJs`, `serverSide`, `adblocker`, `hashtag`, `customCloserId`) | `IntegrationOptions` — see [below](#theme-level-option-integration) |

Model and runtime mapping: [domain/init-options.md](../domain/init-options.md).

## Checklist

1. **Confirm the vendor key.** Find `tarteaucitron.parameters.<jsKey>` in
   `public/tarteaucitron/tarteaucitron.js`, and its default in the `defaults = { … }` block when it
   has one. Use the vendor default as the catalog default: existing configurations fall back to it.
2. **Add the `InitOption`** in `InitOptionCatalog::build()`
   (`src/Consent/Init/InitOptionCatalog.php`), next to the options of the same section:
   - `key` — snake_case, stored in `init_options` JSON and used as the form field name
   - `jsKey` — the exact vendor key, irregular casing included (`DenyAllCta`, `cookieslist`)
   - `type` — `Bool`, `String` or `Choice`
   - `default` — the vendor default
   - `section` — `Essential` (default), `Compliance`, `ConsentMode`, `Display` or `Advanced`: the
     BO tab / card it appears in
   - `choices` — `Choice` only: translation key => stored value
   - `omitIfEmpty` — `String` whose empty value must not reach `init()` (the vendor then applies
     its own fallback)
   - `url: true` — any value the library puts in a link, an attribute or `document.location`:
     only an `http(s)` URL or a relative `/path`, without quotes, `<>`, whitespace or control
     characters, is accepted on save (`LinkConstraints`) **and** on read (`InitOption::isSafeLink()`)
   - `pattern` — `String` only: a PCRE the value must match (form `Regex` constraint, plus
     `NotBlank` when the default is not empty; on read, a non-matching value falls back to the
     default). Use it for values the library writes verbatim somewhere fragile, like
     `cookie_name` / `cookie_domain` in the cookie string
   - `relatedConsentMode` — consent-mode flags only: lists the built-in trackers of that mode under
     the field, with their on/off state (needs a `ConsentMode` case, see
     [consent-alerts.md](../domain/consent-alerts.md))
3. **Translate** in `translations/messages.en.yml` and `messages.fr.yml`, under
   `cyllene_digital_sylius_tarteaucitron.ui`: `<key>` (label, `InitOption::getLabel()`), `<key>_help`
   (help, `InitOption::getHelp()` — mandatory: say what the visitor sees and when to change it), and
   one key per choice. Keep EN and FR symmetric; `AdminTranslationsTest` fails on a missing key.
4. **Document** the row in the "Full catalog" table of
   [domain/init-options.md](../domain/init-options.md) (and "Choices" for a `Choice`), in catalog
   order.
5. **Test** the default and the mapping, next to the existing assertions:
   - `tests/Unit/Sylius/Provider/CatalogConsentDefaultsTest.php` — seeded default
   - `tests/Unit/Consent/Init/InitOptionCatalogTest.php` — section
   - `tests/Unit/Consent/Init/InitOptionsMapperTest.php` — `jsKey` in the `init()` payload
   - `url: true` → a `javascript:` value normalised to `''` in `InitOptionsTest`
   - `pattern` → a non-matching value falls back to the default in `InitOptionsTest`
6. **Changelog / upgrade.** A `CHANGELOG.md` line under `Added`. In `UPGRADE.md`, say that missing
   keys fall back to the catalog default, so no migration is needed.
7. Run the checks:

```bash
vendor/bin/phpunit --colors=always --testsuite=unit
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/ecs check
```

No migration, no form or DataMapper change: `TarteaucitronConfigurationType` builds one field per
catalog entry, and `InitOptions::fromArray()` fills keys absent from stored configurations.

## Worked example: `piwikConsentMode` (tarteaucitron.js 1.35.0)

`tarteaucitron.js` 1.35.0 added `"piwikConsentMode": true` to its defaults, and the upgrade failed
`VendorInitOptionsParityTest` with `["piwikConsentMode"]`. The fix is one catalog entry:

```php
new InitOption(
    'piwik_consent_mode',
    'piwikConsentMode',
    InitOptionType::Bool,
    true,
    InitOptionSection::ConsentMode,
),
```

plus `piwik_consent_mode` / `piwik_consent_mode_help` in both translation files. No
`relatedConsentMode`: there is no built-in Piwik PRO tracker.

## Theme-level option (`integration:`)

1. Add the node under `integration` in `Configuration::getConfigTreeBuilder()`, with the vendor
   default (or `null` when the library default should apply) and an `->info()` line; validate its
   format there when a wrong value would break the page (see `hashtag`, `custom_closer_id`).
2. Add `config key => jsKey` to `IntegrationOptions::JS_KEYS`. `toInit()` leaves out `null` / `''`
   values, and the consent provider merges the result into every channel's payload, after the
   back-office options.
3. Test in `BundleConfigurationTest::testIntegrationOptionsBecomeTheInitPayloadParameter()`.
4. Document the key in [integration/configuration.md](../integration/configuration.md#integration)
   and follow the rest of [Adding a bundle config key](adding-a-bundle-config-key.md) (public
   contract, `CHANGELOG.md`).

## Not exposing an option

When an upstream option must stay out of the BO (callback, value only meaningful in code), list its
`jsKey` in `VendorInitOptionsParityTest::ACKNOWLEDGED_GAPS` and give the reason in the PR. The test
also fails when a listed gap gets exposed later, so the list cannot go stale.

## Public contract

`key` values are stored in the database and read by integrations: renaming or removing one is a
major version. Adding an option is a minor version.
