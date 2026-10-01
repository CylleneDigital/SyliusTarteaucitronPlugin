# Migrations

## Current migration

File: `src/Migrations/Version20260827140000.php`

Description: "Channel-aware tarteaucitron configuration with JSON init_options."

This is the initial migration of the plugin: it creates both tables.

## Schema

- Configuration linked to a **channel** (`channel_id` UNIQUE)
- Options stored as **JSON** (`init_options`, `localized_options`), plus `enabled` and
  `consent_lifetime_days`
- Services in separate table with JSON `parameters`

The migration is written against the DBAL `Schema` API only (`createTable`, `addColumn` with
`Types::*`, named indexes and foreign keys; no platform-specific SQL). CI runs it on **MySQL 8.4,
MariaDB 11.4 and PostgreSQL 17**. Other platforms (SQLite…) are not tested; the migration uses only
portable Schema API calls.

The MariaDB job runs Sylius 2.2: with DBAL 4 (required by Symfony 8, possible from Sylius 2.3), the
Sylius core migrations only recognise MySQL and skip themselves on MariaDB, so `sylius_channel` is
never created and the plugin migration cannot add its foreign key. That is a Sylius limitation:
with DBAL 3 the MariaDB platform extends the MySQL one, and the Sylius migrations run.

CI builds the database from the migrations, then:

```bash
vendor/bin/console doctrine:schema:validate --skip-sync
vendor/bin/console doctrine:schema:update --dump-sql | grep -i cyllene_tarteaucitron
```

The second command must print nothing: any SQL left for a `cyllene_tarteaucitron_*` table means the
migration and the mapping have diverged. Every index and foreign key is named in both so that they
never drift.

### `up()`

Creates both tables with FKs (`FK_TAC_CONFIGURATION_CHANNEL`, `FK_TAC_SERVICE_CONFIGURATION`, both
`ON DELETE CASCADE`) and indexes (unique on `channel_id`). It never drops anything.

Ids follow the ORM's choice for the entities' `AUTO` ids. On MySQL / MariaDB: auto-increment columns.
On **PostgreSQL**: plain `INT` ids plus the sequences `cyllene_tarteaucitron_configuration_id_seq` and
`cyllene_tarteaucitron_service_id_seq`, because every supported Sylius generates ids from sequences
there (ORM default with DBAL 3; with DBAL 4, Sylius 2.3+ forces it through
`identity_generation_preferences`). An identity column would work but leave a drift
(`ALTER id DROP IDENTITY`) in every later `doctrine:migrations:diff` of the shop.

### `down()`

Drops both plugin tables, and the two sequences on PostgreSQL.

## Post-migration procedure

Documented in [UPGRADE.md](../../UPGRADE.md):

1. Run `bin/console doctrine:migrations:migrate`
2. Open back office **Configuration → Tarteaucitron** for **each channel**
3. Enable tarteaucitron and the needed services, then save — this creates the channel row

Without back-office save, tarteaucitron stays **disabled** on shop for that channel.

## Migrations path registration

Via prepend in `CylleneDigitalSyliusTarteaucitronExtension`:

```php
'migrations_paths' => [
    'CylleneDigital\SyliusTarteaucitronPlugin\Migrations' => __DIR__ . '/../Migrations',
],
```

## Adding a future migration

1. Create a class in `src/Migrations/`
2. Use the DBAL `Schema` API (not raw SQL) so it stays portable, and name indexes / foreign keys as
   in the mapping
3. Check the drift command above prints nothing on MySQL and PostgreSQL
4. Document breaking changes in `UPGRADE.md`
5. If renaming JSON keys → major bump + data migration guide

Do not modify `Version20260827140000` once published to production.
