# Database schema

## Relational diagram

```
sylius_channel (Sylius core)
       │
       │ 1:1 (UNIQUE channel_id)
       ▼
cyllene_tarteaucitron_configuration
       │
       │ 1:N
       ▼
cyllene_tarteaucitron_service
```

## Table `cyllene_tarteaucitron_configuration`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT (auto) | PK |
| `channel_id` | INT | NOT NULL, FK → `sylius_channel(id)` ON DELETE CASCADE, **UNIQUE** |
| `enabled` | BOOLEAN | NOT NULL, entity default `false` |
| `localized_options` | JSON | NOT NULL — per-locale links and banner texts, `{localeCode: {key: value}}` |
| `consent_lifetime_days` | SMALLINT | NOT NULL, entity default `180` (1–364, validated in the form) |
| `init_options` | JSON | NOT NULL — snake_case init options |

Indexes: `uniq_tac_configuration_channel` (also serves the channel FK). Foreign key:
`FK_TAC_CONFIGURATION_CHANNEL`.

## Table `cyllene_tarteaucitron_service`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT (auto) | PK |
| `configuration_id` | INT | NOT NULL, FK → configuration ON DELETE CASCADE |
| `type` | VARCHAR(50) | NOT NULL — tarteaucitron job key |
| `enabled` | BOOLEAN | NOT NULL |
| `parameters` | JSON | NOT NULL — BO key → value map |

Indexes: `uniq_tac_service_type (configuration_id, type)`, `IDX_TAC_SERVICE_CONFIGURATION`. Foreign
key: `FK_TAC_SERVICE_CONFIGURATION`.

## Doctrine entities

### `TarteaucitronConfiguration`

- Implements `ChannelAwareInterface`
- `services` collection indexed by `type` (`indexBy: 'type'`)
- Cascade persist/remove on services, orphanRemoval

### `TarteaucitronService`

- No dedicated repository — always read and written through the parent configuration's collection
- `parameters` JSON is `array<string, string>` (BO TextType values)
- `getParameter(key, default = ''): string` / `setParameter(key, string)`

## Repository

Only the configuration has a repository:
`TarteaucitronConfigurationRepository::findOneByChannel(ChannelInterface $channel): ?TarteaucitronConfiguration`
(fetch-joins the services).

It extends `ServiceEntityRepository` and is registered with the `doctrine.repository_service` tag,
so the DI service and `$em->getRepository(TarteaucitronConfiguration::class)` are the same instance.
Interface: `TarteaucitronConfigurationRepositoryInterface` (DI alias, used for tests). Both are
internal.

## Shop runtime behavior

`ConsentConfigurationProvider::resolve()`:

| Situation | Result |
|-----------|--------|
| No channel (empty ChannelContext) | `enabled: false`, empty init/services |
| Channel with no configuration row | `enabled: false` |
| Existing configuration | `enabled` + mapped init (with `integration:` options) + `TrackerRuntimeState` list + consent lifetime (clamped to 1–364) + normalized `localized_options` |

Request-scoped cache by channel ID; reset via `ResetInterface` between requests/tests.

## JSON `init_options`

Minimal example after seed:

```json
{
  "privacy_url": "",
  "high_privacy": true,
  "deny_all_cta": true,
  "cookie_name": "tarteaucitron",
  ...
}
```

Unknown keys ignored on read. Missing keys filled by `InitOptionCatalog`.

## JSON `parameters` (service)

gtag example:

```json
{
  "gtag_ua": "G-XXXXXXXXXX"
}
```

Keys match `TrackerParameter::key` from the definition.

## Database compatibility

Migration `Version20260827140000` uses only the DBAL `Schema` API, so Doctrine picks the column types
of the platform (for instance `TINYINT(1)` on MySQL / MariaDB, `BOOLEAN` on PostgreSQL). CI runs it on
MySQL 8.4, MariaDB 11.4 and PostgreSQL 17 — see [Migrations](migrations.md).
