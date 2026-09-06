<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds optional job_date column to invoice table.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('invoice')) {
            return;
        }

        $invoice = $schema->getTable('invoice');
        if (!$invoice->hasColumn('job_date')) {
            $invoice->addColumn('job_date', 'date_immutable', ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('invoice')) {
            return;
        }

        $invoice = $schema->getTable('invoice');
        if ($invoice->hasColumn('job_date')) {
            $invoice->dropColumn('job_date');
        }
    }
}
