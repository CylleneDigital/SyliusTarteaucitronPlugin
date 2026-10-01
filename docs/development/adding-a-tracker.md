# Adding a tracker

tarteaucitron.js ships about 250 services. This plugin registers a subset as **one PHP class per job key**. Adding the next service must not touch the registry, renderer, forms, or DI extension.

## Checklist

1. Confirm the job key in `public/tarteaucitron/tarteaucitron.services.min.js` and the `tarteaucitron.user.*` keys the vendor expects.
2. Create `src/Tracker/{Category}/{Name}Tracker.php` extending `AbstractTrackerDefinition`.
3. Implement `getType()` (exact vendor job key) and `getCategory()`.
4. Override only what is not the default:
   - `getParameters()` — BO / `tarteaucitron.user.*` mapping
   - `isEmbed()` — HTML placeholders (YouTube, Maps, …)
   - `getConsentMode()` — Google / Bing / GTM
   - `getAdminHintTranslationKey()` — use `TrackerAdminHints` when applicable
   - `isSeededByDefault()` — keep `true` so the service appears in the back office, switched off
5. Add `cyllene_digital_sylius_tarteaucitron.ui.service_{type}` and one `cyllene_digital_sylius_tarteaucitron.ui.{parameter key}` per parameter in `translations/messages.en.yml` and `messages.fr.yml`.
   Mark optional parameters `required: false`.
6. Add the row to the built-in table of [domain/trackers.md](../domain/trackers.md) and update the count in its heading.
7. Run `composer test` and `composer phpstan`. Nothing to register in the tests: `TrackerTestKit` discovers
   `src/Tracker/**/*Tracker.php`, and `VendorLibraryParityTest` checks the job key and `user.*` keys
   against the vendored library.

`config/services/trackers.php` prototypes `src/Tracker/**/*Tracker.php` and tags them `cyllene_digital_sylius_tarteaucitron.tracker`. No Extension change.

## Template (script + ID)

```php
final class ExampleTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'example';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Analytic;
    }

    public function getParameters(): array
    {
        return [
            new TrackerParameter('example_id', 'exampleId', placeholder: 'XXXX'),
        ];
    }
}
```

## Template (embed)

```php
final class ExampleEmbedTracker extends AbstractTrackerDefinition
{
    public function getType(): string
    {
        return 'exampleembed';
    }

    public function getCategory(): TrackerCategory
    {
        return TrackerCategory::Video;
    }

    public function isEmbed(): bool
    {
        return true;
    }
}
```

## First-use behaviour

New channel configuration: tarteaucitron **disabled**, all seeded services **disabled**. The admin enables the plugin and only the services they need, then saves.

## Public contract

Do not change job keys (stored in `cyllene_tarteaucitron_service.type`), or remove a method from
`TrackerDefinitionInterface`, or rename the DI tag without a major version. Adding a method to the
interface is allowed in a minor version only with a default in `AbstractTrackerDefinition`.
