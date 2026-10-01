# Back-office forms

All form types and data mappers are **internal** (`@internal`).

## Form types

| Class | Data | Role |
|-------|------|------|
| `TarteaucitronConfigurationType` | `TarteaucitronConfiguration` | Enabled flag, consent lifetime, init options, localized options, services collection |
| `LocalizedOptionsType` | `array` (unmapped) | One block per channel locale: two links + eight banner texts |
| `TarteaucitronServiceType` | `TarteaucitronService` | Enabled switch + dynamic parameters |

Block prefixes:

- `cyllene_digital_sylius_tarteaucitron_configuration`
- `cyllene_digital_sylius_tarteaucitron_service`

## Configuration — fixed fields

| Field | Type | Mapped | Source |
|-------|------|--------|--------|
| `enabled` | `CheckboxType` | via DataMapper | `enabled` column |
| `consent_lifetime_days` | `IntegerType`, `NotBlank` + `Range(1, 364)` | via DataMapper | `consent_lifetime_days` column |
| `services` | `CollectionType` of `TarteaucitronServiceType` | yes | OneToMany relation |

`364` is `TarteaucitronConfiguration::MAX_CONSENT_LIFETIME_DAYS`; the default is `180`
(`DEFAULT_CONSENT_LIFETIME_DAYS`). A value above 180 is allowed but raises a CNIL warning in the back
office (`ComplianceCheck`), it does not block the save.

## Configuration — init fields (unmapped)

All `InitOptionCatalog` options are added dynamically in `PRE_SET_DATA`:

```php
$form->add($option->key, $type, [
    'label' => $option->getLabel(),
    'help' => $option->getHelp(),
    'required' => false,
    'mapped' => false,
    'data' => $data,
    // + 'choices' for Choice options, + 'constraints' (see Validation)
]);
```

Symfony types per `InitOptionType`: `Bool` → `CheckboxType`, `Choice` → `ChoiceType`, `String` →
`TextType`.

## Configuration — `localized_options` (unmapped)

Added in `PRE_SET_DATA` as a `LocalizedOptionsType` (`mapped: false`), with:

- `locales`: the channel locales (code => name), the channel default locale first;
- `default_links`: the channel-wide `privacy_url` / `readmore_link`, shown as placeholders;
- `data`: `LocalizedOptions::normalize()` of the stored `localized_options`.

For each locale, `LocalizedOptionsType` adds:

| Fields | Type | Placeholder |
|--------|------|-------------|
| `privacy_url`, `readmore_link` | `TextType` | Channel-wide link |
| `middle_bar_head`, `accept_all`, `deny_all`, `personalize`, `close` | `TextType` | Library text in that language |
| `alert_big_privacy`, `disclaimer`, `mandatory_text` | `TextareaType` | Library text in that language |

The library texts come from the vendored `lang/` files (`VendorLanguageTexts`). An empty field keeps
the channel-wide link or the library text.

## `TarteaucitronConfigurationDataMapper`

Responsibility: bridge between the form and the entity columns.

### `mapDataToForms`

- Fills `enabled`, `consent_lifetime_days`, `services`, and each init key via `InitOptions::fromArray()`.
- `localized_options` gets its data from the form type (`data` option), not from the mapper.

### `mapFormsToData`

- Writes `enabled` and `consent_lifetime_days` (when it is an integer).
- `localized_options`: `LocalizedOptions::normalize()` of the submitted values, plus the stored
  values of locales no longer on the channel (kept, not erased).
- Aggregates all init form keys → `InitOptions::fromArray($raw)` → `setInitOptions()`.

The `services` CollectionType remains managed by Symfony (not this mapper).

## Service fields

| Field | Mapped | Notes |
|-------|--------|-------|
| `type` | yes | `HiddenType` — job key |
| `enabled` | yes | `CheckboxType` with `label: false`; the admin template renders it **by hand** as a switch labelled with the service name |
| `{parameter.key}` | **no** | Added dynamically in `PRE_SET_DATA` |

## `TarteaucitronServiceDataMapper`

Maps `enabled` + unmapped parameters ↔ JSON `parameters` on entity.

Parameter fields generated from `TrackerDefinitionInterface::getParameters()`:

```php
$form->add($parameter->key, TextType::class, [
    'label' => $parameter->getLabel(),
    'required' => false,
    'mapped' => false,
    'data' => $service->getParameter($parameter->key),
    'attr' => ['placeholder' => $parameter->placeholder],
]);
```

On write, the DataMapper keeps only string form values (`is_string($data) ? $data : ''`).

If type unknown to registry → no parameter fields (enabled only).

## Services collection

```php
->add('services', CollectionType::class, [
    'entry_type' => TarteaucitronServiceType::class,
    'allow_add' => false,
    'allow_delete' => false,
    'label' => 'cyllene_digital_sylius_tarteaucitron.ui.services',
])
```

Services are created by the synchronizer, not manually by admin. No add/remove.

## Validation

| Field | Rule |
|-------|------|
| `consent_lifetime_days` | `NotBlank`, `Range(min: 1, max: 364)` |
| `privacy_url`, `readmore_link` (channel-wide and per locale) | `LinkConstraints::create()`: empty, an `http` / `https` URL, or a relative path starting with a single `/`; and never a quote, `<`, `>`, backtick, backslash, whitespace or control character |
| `icon_src` | `LinkConstraints::create(true)`: same as links, or a raster base64 `data:` URI (`data:image/png\|jpeg\|jpg\|gif\|webp;base64,…`; SVG is refused) |
| Options with a `pattern` (`cookie_name`, `cookie_domain`) | `Regex($pattern)`, plus `NotBlank` when the default is not empty (`cookie_name`) |
| Localized texts | `Length(max: 500)` and no `<`, `>` or `"` |
| Entity | `#[Assert\NotNull]` on `initOptions` |

Patterns (`InitOption::$pattern`, in `InitOptionCatalog`):

- `cookie_name`: an RFC 6265 cookie-name token, `` /^[A-Za-z0-9!#$%&'*+.^_`|~-]+$/ ``;
- `cookie_domain`: a host name, optionally with a leading dot,
  `/^\.?[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)*$/`.

The same rules are applied again when the values are read (`InitOption::normalize()`,
`InitOption::isSafeLink()`, `LocalizedOptions::normalize()`): a value written straight into the
database that breaks them falls back to the default (or is dropped) instead of reaching the shop.

Required tracker parameters are **not** validated on submit: they are checked at JS render
(`areRequiredParametersSatisfied`). The back office can save an enabled tracker without its ID — it
is flagged "incomplete" in the services column and simply not emitted on the shop.

CNIL guidance (`ComplianceCheck`) produces warnings, never validation errors.

## Translations

Domain: `messages` (labels), `validators` (localized text message), `flashes` (save message).
Key prefix: `cyllene_digital_sylius_tarteaucitron.*`

Files: `translations/messages.{en,fr}.yml`, `translations/validators.{en,fr}.yml`,
`translations/flashes.{en,fr}.yml`
