# Admin Twig Hooks

Configuration: `config/twig_hooks/admin/configuration/update.yaml`

Injected via `CylleneDigitalSyliusTarteaucitronExtension::prepend()`.

## Page layout

Template `admin/configuration.html.twig` triggers:

```twig
{% hook 'update' with { _prefixes: prefixes, form, channel, channels } %}
```

Sylius Admin resolves hooks prefixed `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.*`.

## Configured hooks

| Full hook | Hookable | Action |
|-----------|----------|--------|
| `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.content.header` | `breadcrumbs` | **disabled** |
| `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.content.header.title_block` | `title` | Title template + channel selector |
| `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.content.header.title_block.actions` | `show`, `update`, `cancel` | **disabled** (`show` would fail with a 500 on Sylius 2.1: there is no `configuration` resource) |
| `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.content.header.title_block.actions` | `save` | Custom save button |
| `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.content.form.sections.general` | `default` | **disabled** |
| `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.content.form.sections.general` | `tarteaucitron` | **Plugin form** |

## Main form template

`templates/admin/configuration/update/content/form/sections/general/tarteaucitron.html.twig`

Structure:

- **Compliance summary** (above the tabs): when `tarteaucitron_compliance_findings(form)` returns
  anything, a warning lists every setting that moves away from the CNIL cookie guidance
  (`ComplianceCheck`: high privacy off, "Accept all" shown without "Deny all", services allowed by
  default, consent lifetime above 180 days). These are warnings only: they never block the save.
- **Left column (8)**: init options in tabs via `tarteaucitron_init_tabs()`:
  - **Essentials**: `enabled`, privacy / "read more" links, consent lifetime;
  - **Compliance**: consent collection card, then the Consent Mode card (each option boxed, with the
    related services and the Google / Bing alert);
  - **Texts and languages**: one accordion item per channel locale, with the two links and the eight
    banner texts (`localized_options`);
  - **Appearance**: display options;
  - **Advanced**: `cookie_name`, `cookie_domain`, `data_layer`.

  Badges on tab titles:
  - **validation errors** are counted per tab by Sylius itself (`.tab-error`); the plugin only opens
    the first tab holding an error (`data-error-tab`, also applied server-side);
  - the **plugin badge** (yellow) counts the CNIL warnings of the tab; each warned field also shows
    its own warning under the input.

  The active tab is kept in the URL fragment (`#tab-<id>`), which also rides on the form action so
  saving returns to the same tab.
- **Right column (4)**: services accordion via `tarteaucitron_group_services_by_category()`:
  - one compact row per service: a switch labelled with the service name (the `enabled` checkbox,
    rendered by hand) and, for services tied to a consent mode, a badge;
  - the admin hint, the "incomplete" warning and the identifier fields stay folded until the service
    is switched on (or holds a validation error);
  - each category header counts enabled / available services, updated live when a switch changes;
  - a search box filters services by service or category name (case and accent insensitive).

In the Consent Mode card, the badges listing the services related to a mode show each tracker's
label (`tarteaucitron_service_admin_meta(type).label`) and its on / off state.

Admin assets: `public/admin/tarteaucitron-admin.css` and `public/admin/tarteaucitron-admin.js` (switch counters, search box, Consent Mode badges).

## Auxiliary templates

| File | Role |
|------|------|
| `.../title_block/title.html.twig` | Page title + current channel (always shown; dropdown when several) |
| `.../title_block/actions/save.html.twig` | Save button |

## Admin Twig functions

Defined in `TarteaucitronExtension`, implemented by `TarteaucitronAdminRuntime`. They exist for the
plugin's own admin template only and are **internal**: they can change in a minor version.

| Function | Role |
|----------|------|
| `tarteaucitron_init_tabs()` | Tabs → cards → options, from `InitOptionSection` |
| `tarteaucitron_init_tabs_state(form, tabs)` | tab to open (first one holding an invalid field) and CNIL findings per tab |
| `tarteaucitron_admin_asset_version(file)` | fingerprint of a file under `public/admin/` (the page stylesheet and script), appended as `?v=` |
| `tarteaucitron_compliance_findings(form)` | field → message map of CNIL guidance warnings |
| `tarteaucitron_option_conflicts(form)` | option key → translation key of options tarteaucitron.js ignores in the current setup (`OptionConflicts`, info, not CNIL) |
| `tarteaucitron_group_services_by_category(form.services)` | Accordion by category |
| `tarteaucitron_service_enabled_map(form.services)` | type → bool map for badges |
| `tarteaucitron_trackers_by_consent_mode(mode)` | Types linked to a ConsentMode |
| `tarteaucitron_consent_alert(key, map)` | Google/Bing alerts |
| `tarteaucitron_service_admin_meta(type, enabled, parameters)` | Label + hint + consent badge + incomplete warning |

See also [Consent mode alerts](../domain/consent-alerts.md).

## Disabling default Sylius hooks

Hookables `default`, `show`, `update`, `cancel`, `breadcrumbs` are disabled to avoid standard Sylius CRUD layout (this page is a singleton per channel, not a classic Sylius resource).
