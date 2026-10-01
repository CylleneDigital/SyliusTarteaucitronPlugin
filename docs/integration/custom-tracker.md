# Custom tracker

## When to create a custom tracker

- tarteaucitron service not already implemented under `src/Tracker/`
- Shop-specific parameters or category
- Custom integration with documented vendor job key

To contribute a built-in service to this plugin, see [Adding a tracker](../development/adding-a-tracker.md).

## Without code: declare it in configuration

Any service of the vendored `tarteaucitron.services.js` can be offered in the back office from
bundle configuration, like a built-in one (on/off switch, parameters, "incomplete" warning). Every
key of an entry is described in the [bundle configuration reference](configuration.md#trackers):

```yaml
# config/packages/cyllene_digital_sylius_tarteaucitron.yaml
cyllene_digital_sylius_tarteaucitron:
    trackers:
        smartsupp:                      # tarteaucitron job key
            category: support           # analytic, ads, api, video, social, support, comment, other
            label: Smartsupp            # back-office name (translation key or text), default: the job key
            parameters:
                smartsuppKey:           # exact tarteaucitron.user.* key
                    placeholder: 'xxxxxxxx'
                    # required: true    # default; false for optional keys
                    # label: 'Smartsupp key'
            # embed: true               # for services replacing HTML placeholders (videos, widgets)
```

The container build **fails** when the job key does not exist in the vendored library, or when a
parameter is not a `tarteaucitron.user.*` key that service reads - a typo would otherwise give a
service that shows up in the back office and never loads. A job key already used by a built-in
tracker fails too (`UniqueTrackerTypePass`).

Labels: the service shows `label` (translation key or plain text), or the job key; each parameter
shows its `label`, or its `user.*` key. The stored parameter key is the `user.*` key itself.

Use a PHP class (below) only for what configuration cannot express: an admin hint, a consent-mode
link, or parameters stored under a different key than the `user.*` one.

## With a PHP class

### 1. Implement the definition

Extend `AbstractTrackerDefinition`. It is the supported extension point: a method added to
`TrackerDefinitionInterface` in a minor version always gets a default implementation there.
Implementing the interface directly is possible but at your own risk - a minor upgrade may require
you to add the new method.

```php
<?php

declare(strict_types=1);

namespace App\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\AbstractTrackerDefinition;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerParameter;

final class AcmeTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'acme'; // tarteaucitron job key - must be unique
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Other;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('acme_id', 'acmeId', placeholder: 'XXXX'),
        ];
    }
}
```

### 2. Register the Symfony service

With autoconfiguration (the default `services.yaml` of a Symfony application, `App\` resource),
there is nothing to add: any service implementing `TrackerDefinitionInterface` receives the tag
`cyllene_digital_sylius_tarteaucitron.tracker`.

Otherwise, tag the service:

```yaml
App\Consent\AcmeTracker:
    tags:
        - { name: cyllene_digital_sylius_tarteaucitron.tracker }
```

### 3. Translate

The back office shows translation keys; add them to your application translations
(`translations/messages.<locale>.yaml`):

```yaml
cyllene_digital_sylius_tarteaucitron:
    ui:
        service_acme: Acme          # service name: ui.service_{type}, from getLabel()
        acme_id: Acme site ID       # parameter: ui.{parameter key}, unless TrackerParameter has a label
```

### 4. Verify type uniqueness

The job key must not be used by another tracker. Two trackers on the same job key fail the container
build (`UniqueTrackerTypePass`) when the pass can read the type without running the app: `trackers:`
entries, and classes whose service definition passes no argument, method call, factory or
configurator. Otherwise (a class receiving its job key or dependencies from its definition), the
duplicate surfaces at runtime instead: `TrackerRegistry` throws `InvalidArgumentException`.

### 5. Reseed back office

Open the Tarteaucitron page for each channel:

- `ServiceCatalogSynchronizer` adds the row, switched off (`isSeededByDefault()`, `true` by default:
  the back office cannot add a row itself)
- Switch the service on, fill its parameters and save

## `TrackerParameter`

| Property | Role |
|----------|------|
| `key` | Key stored in JSON `parameters` |
| `userKey` | `tarteaucitron.user.{userKey}` suffix; a plain JavaScript identifier (`[A-Za-z_][A-Za-z0-9_]*`), anything else throws |
| `required` | Default `true` - blocks JS render if empty |
| `placeholder` | Back-office field placeholder |
| `label` | Optional back-office label (translation key or plain text) |

Back-office label: `label` when given, otherwise the translation key
`cyllene_digital_sylius_tarteaucitron.ui.{key}` (add it to your translations).

## Useful overrides

| Method | Default | Use case |
|--------|---------|----------|
| `isEmbed()` | `false` | HTML placeholder widget |
| `isSeededByDefault()` | `true` | Keep `true`: with `false` the service never appears in the back office and cannot be switched on |
| `getConsentMode()` | `null` | Link Google/Bing/GTM |
| `getAdminHintTranslationKey()` | `null` | Hint under service |
| `getLabel()` | `cyllene_digital_sylius_tarteaucitron.ui.service_{type}` | Custom label |

## Render validation

`areRequiredParametersFilled()` (AbstractTrackerDefinition): true when every `required: true`
parameter is non-empty. Disabled services never reach it: the shop only reads enabled ones.

## Generated script

For `acme_id = "12345"`:

```javascript
tarteaucitron.user.acmeId = "12345";
(tarteaucitron.job = tarteaucitron.job || []).push("acme");
```

Job key `acme` must exist in tarteaucitron.js (vendor service or app custom JS).

## Public contract

`AbstractTrackerDefinition`, `TrackerDefinitionInterface`, `TrackerParameter`, `TrackerCategory`,
`ConsentMode`, the DI tag and the translation key conventions are public API. The registry and the
other services are internal.

See [Public contract](../architecture/public-contract.md).
