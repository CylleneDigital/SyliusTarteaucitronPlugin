# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - Unreleased

Initial public release.

### Added

- Official tarteaucitron.js free install on Sylius 2 shops, one configuration per channel, the
  library vendored (1.35.0) and served from the shop: no CDN, no call to tarteaucitron.io.
- Sylius 2.1, 2.2 and 2.3 on Symfony 7.4, and Symfony 8 with Sylius 2.3; PHP 8.2 or later (8.3 for
  Sylius 2.3, 8.4 for Symfony 8). MySQL, MariaDB and PostgreSQL.
- Back office (**Configuration → Tarteaucitron**) in tabs: Essentials, Compliance, Texts and
  languages, Appearance, Advanced; current channel in the header with a channel switcher.
- Every `tarteaucitron.init()` option the library reads, with a help text (EN/FR); theme-level ones
  (`use_external_css`, `mandatory_cta`, `use_external_js`, `server_side`, `adblocker`, `hashtag`,
  `custom_closer_id`)
  in the `integration:` bundle configuration instead.
- CNIL guidance warnings (implied consent, hidden "Deny all", services accepted by default, consent
  kept more than 6 months): summary, tab badges and per-field warnings; saving is never blocked.
- Back-office notes on options tarteaucitron.js ignores in the current setup (grouping with the
  ad-blocker detection, compact banner with the floating icon), and a summary of enabled services
  missing a required identifier.
- Consent lifetime per channel (default 180 days, at most 364), emitted as `tarteaucitronForceExpire`.
- Per channel locale: privacy / read-more links and eight banner texts, with the library text as
  placeholder; emitted as `tarteaucitronCustomText`. Banner language follows the shop locale
  (`locale_aliases` for codes the library spells differently).
- 46 built-in trackers (analytics, ads, APIs, videos, social, support, other), with Google and Bing
  Consent Mode links; compact services column with search and per-category counts.
- `trackers:` bundle configuration to offer any vendor service without a PHP class, validated
  against the vendored library at container build; `AbstractTrackerDefinition` for custom PHP
  trackers.
- CSP nonce on the plugin's script tags: per response (`script_nonce_provider: nelmio` for
  NelmioSecurityBundle, or your `ScriptNonceProviderInterface` service) or fixed (`script_nonce`).
- Content fingerprint on the plugin stylesheet and library URLs (`tarteaucitron_asset_version()`).
- `bin/update-tarteaucitron.sh` to vendor an upstream tag with its provenance, and parity tests that
  fail when a library upgrade brings a new init option or `user.*` key.

[Unreleased]: https://github.com/CylleneDigital/SyliusTarteaucitronPlugin/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/CylleneDigital/SyliusTarteaucitronPlugin/releases/tag/v1.0.0
