# Consent mode alerts (admin)

## `ConsentAlert` class

File: `src/Consent/ConsentAlert.php`

Purpose: display hints in the back office when a consent mode option is enabled but associated trackers are not (or configuration is inconsistent).

Used via Twig: `tarteaucitron_consent_alert(optionKey, serviceEnabled)`.

## Input

- `optionKey` - snake_case init option key (`google_consent_mode`, `bing_consent_mode`)
- `serviceEnabled` - map `{ type => bool }` built by `tarteaucitron_service_enabled_map()`

## Google rules (`google_consent_mode`)

Evaluates trackers for `ConsentMode::Google` and `ConsentMode::Gtm`.

| Condition | Level | Message (key under `cyllene_digital_sylius_tarteaucitron.ui.`) |
|-----------|-------|---------------------------|
| GTM enabled, no native Google tracker enabled | `warning` | `google_consent_mode_gtm_warning` |
| Neither Google nor GTM enabled | `info` | `google_consent_mode_no_service_hint` |
| At least one Google tracker enabled (GTM on or off) | - | no alert |

Google mode trackers: `gtag`, `googleads`  
GTM mode trackers: `googletagmanager`

## Bing rules (`bing_consent_mode`)

Bing mode trackers: `clarity`, `bingads`

| Condition | Level | Message |
|-----------|-------|---------|
| No Bing tracker enabled | `info` | `bing_consent_mode_no_service_hint` |
| At least one enabled | - | no alert |

## Back-office display

In `templates/admin/.../tarteaucitron.html.twig`:

- Alerts shown only when the consent option is **checked** (`attribute(form, option.key).vars.data`)
- Green/grey badges list the linked trackers by their label, with their on/off state
- `ConsentAlert` does not block save - informational hints only

## Extension

To add an alert for a new option:

1. Add `relatedConsentMode` on the relevant `InitOption`
2. Extend `ConsentAlert::forOption()` with a new `match` arm
3. Add translation keys in `translations/messages.*.yml`

## CNIL guidance warnings (`ComplianceCheck`)

Separate from the consent-mode alerts above: `ComplianceCheck::check()` flags settings that depart
from the CNIL cookie guidance. They are **warnings only** - saving is never blocked, the admin keeps
the last word.

| Field | Flagged when | Why |
|---|---|---|
| `high_privacy` | off | browsing on counts as acceptance (implied consent) |
| `deny_all_cta` | off while "Accept all" is on the banner (`accept_all_cta` on, or `high_privacy` off) | refusing must be as easy as accepting |
| `service_default_state` | `true` | services load before any choice |
| `consent_lifetime_days` | more than 180 | the CNIL recommends asking again after 6 months |

Rendering: a summary above the tabs ("N settings depart from the CNIL guidance"), a yellow count
badge on each tab title (validation-error badges are Sylius's own), and a warning under each flagged
field. `tarteaucitron_compliance_findings(form)` reads
the values the form shows, submitted ones included.

Deliberately **not** flagged: `soft_consent_mode` off (the default). With a consent mode on, it loads
the linked tags in restricted mode before consent (Google Consent Mode "advanced"); whether that
is acceptable is a legal call per shop, not a default the plugin should mark as wrong.
