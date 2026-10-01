# Configuration flow (back office)

## Entry point

| Element | Value |
|---------|-------|
| Route | `cyllene_digital_sylius_tarteaucitron_admin_configuration` |
| Path | `/admin/tarteaucitron` (Sylius admin prefix) |
| Controller | `TarteaucitronConfigurationAction` (invokable) |
| Security | `#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]` |
| Menu | Configuration → Tarteaucitron (`AdminMenuListener`) |

## GET sequence (form display)

```
Request (?channelCode=WEB)
  → AdminChannelResolver::fromRequest()
  → Repository::findOneByChannel()
       ├─ null → Factory::createForChannel() + ensureSeeded()
       └─ exists → Factory::ensureCatalog()
  → FormFactory::create(TarteaucitronConfigurationType)
  → handleRequest() (no-op on GET)
  → render admin/configuration.html.twig
       └─ hook 'update' (Sylius Admin UI)
```

## POST/PUT sequence (save)

```
Form submitted + valid
  → TarteaucitronConfigurationDataMapper writes enabled, consent_lifetime_days,
    init_options and localized_options on the entity
  → EntityManager::persist($configuration)
  → flush()
  → Flash success (cyllene_digital_sylius_tarteaucitron.ui.configuration_saved)
  → Redirect GET with same channelCode
```

Submitted but invalid: the page is rendered again with the errors (no redirect). The tab holding
the first error is opened; see [Admin Twig Hooks](twig-hooks-admin.md).

## Factory

`TarteaucitronConfigurationFactory`:

| Method | Action |
|--------|--------|
| `createForChannel()` | New entity + init defaults + seed services |
| `ensureCatalog()` | Adds missing trackers on existing entity |

## Synchronizer

`ServiceCatalogSynchronizer::ensureSeeded()` — see [Trackers](../domain/trackers.md).

Does not persist: the controller `flush()` after form validation.

## Main template

`templates/admin/configuration.html.twig`:

```twig
{% hook 'update' with { _prefixes: prefixes, form, channel, channels } %}
```

Twig Hook prefixes:

- `cyllene_digital_sylius_tarteaucitron.admin.configuration`
- `sylius_admin.common`

so the page resolves `cyllene_digital_sylius_tarteaucitron.admin.configuration.update.*` first, then
falls back to `sylius_admin.common.update.*`.

Plugin content is injected by the `tarteaucitron` hookable of
`cyllene_digital_sylius_tarteaucitron.admin.configuration.update.content.form.sections.general`
(see [Admin Twig Hooks](twig-hooks-admin.md)).

## Channel selector

The title template (`title.html.twig`) receives `channel` and `channels` to switch between channels via `?channelCode=`.

## "Unconfigured" state on shop

Until a save has occurred:

- No DB row → shop sees tarteaucitron **off**
- First back-office open creates an in-memory entity with `enabled = false` and all services disabled
- After save without checking enabled → shop stays **off**
- Admin must enable tarteaucitron and the services they need, then save

## Diagram

See [Admin sequence](../diagrams/admin-save-sequence.md).
