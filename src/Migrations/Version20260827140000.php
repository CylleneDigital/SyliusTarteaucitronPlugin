<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * @see UPGRADE.md
 *
 * @internal
 */
final class Version20260827140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Channel-aware tarteaucitron configuration with JSON init_options.';
    }

    public function up(Schema $schema): void
    {
        $sequences = $this->idsComeFromSequences();

        $configuration = $schema->createTable('cyllene_tarteaucitron_configuration');
        $configuration->addColumn('id', Types::INTEGER, ['autoincrement' => !$sequences]);
        $configuration->addColumn('channel_id', Types::INTEGER);
        $configuration->addColumn('enabled', Types::BOOLEAN);
        $configuration->addColumn('consent_lifetime_days', Types::SMALLINT);
        $configuration->addColumn('init_options', Types::JSON);
        $configuration->addColumn('localized_options', Types::JSON);
        $configuration->setPrimaryKey(['id']);
        $configuration->addUniqueIndex(['channel_id'], 'uniq_tac_configuration_channel');
        $configuration->addForeignKeyConstraint(
            'sylius_channel',
            ['channel_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_TAC_CONFIGURATION_CHANNEL',
        );

        $service = $schema->createTable('cyllene_tarteaucitron_service');
        $service->addColumn('id', Types::INTEGER, ['autoincrement' => !$sequences]);
        $service->addColumn('configuration_id', Types::INTEGER);
        $service->addColumn('type', Types::STRING, ['length' => 50]);
        $service->addColumn('enabled', Types::BOOLEAN);
        $service->addColumn('parameters', Types::JSON);
        $service->setPrimaryKey(['id']);
        $service->addIndex(['configuration_id'], 'IDX_TAC_SERVICE_CONFIGURATION');
        $service->addUniqueIndex(['configuration_id', 'type'], 'uniq_tac_service_type');
        $service->addForeignKeyConstraint(
            'cyllene_tarteaucitron_configuration',
            ['configuration_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'FK_TAC_SERVICE_CONFIGURATION',
        );

        if ($sequences) {
            $schema->createSequence('cyllene_tarteaucitron_configuration_id_seq');
            $schema->createSequence('cyllene_tarteaucitron_service_id_seq');
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('cyllene_tarteaucitron_service');
        $schema->dropTable('cyllene_tarteaucitron_configuration');

        if ($this->idsComeFromSequences()) {
            $schema->dropSequence('cyllene_tarteaucitron_service_id_seq');
            $schema->dropSequence('cyllene_tarteaucitron_configuration_id_seq');
        }
    }

    /**
     * On PostgreSQL the entities' AUTO ids come from a sequence with every Sylius this plugin
     * supports: the ORM default with DBAL 3, forced by Sylius 2.3+ (identity_generation_preferences)
     * with DBAL 4. An identity column would leave a drift against the mapping.
     */
    private function idsComeFromSequences(): bool
    {
        return $this->platform instanceof PostgreSQLPlatform;
    }
}
