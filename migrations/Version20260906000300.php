<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair migration: ensure invoice.job_date exists on SQLite and PostgreSQL even under schema drift.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['invoice'])) {
            return;
        }

        $invoice = $schemaManager->introspectTable('invoice');
        if ($invoice->hasColumn('job_date')) {
            return;
        }

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('ALTER TABLE invoice ADD COLUMN job_date DATE DEFAULT NULL');

            return;
        }

        if ($platform instanceof SQLitePlatform) {
            $this->addSql('ALTER TABLE invoice ADD COLUMN job_date DATE DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Intentionally no-op: dropping a column on SQLite requires table rebuild and this is a repair migration.
    }
}
