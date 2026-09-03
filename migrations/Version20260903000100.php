<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repairs local schema drift by creating customer and invoice series tables and adding nullable relations to invoices.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('customer')) {
            $customer = $schema->createTable('customer');
            $customer->addColumn('id', 'bigint', ['autoincrement' => true]);
            $customer->addColumn('name', 'string', ['length' => 255]);
            $customer->addColumn('nif_cif', 'string', ['length' => 20]);
            $customer->addColumn('address', 'text');
            $customer->addColumn('email', 'string', ['length' => 255]);
            $customer->addColumn('phone', 'string', ['length' => 30, 'notnull' => false]);
            $customer->addColumn('created_at', 'datetime_immutable');
            $customer->addColumn('updated_at', 'datetime_immutable');
            $customer->setPrimaryKey(['id']);
            $customer->addUniqueIndex(['nif_cif'], 'uniq_customer_nif_cif');
            $customer->addIndex(['name'], 'idx_customer_name');
        }

        if (!$schema->hasTable('invoice_series')) {
            $series = $schema->createTable('invoice_series');
            $series->addColumn('id', 'bigint', ['autoincrement' => true]);
            $series->addColumn('prefix', 'string', ['length' => 20]);
            $series->addColumn('year', 'integer');
            $series->addColumn('next_number', 'bigint');
            $series->addColumn('created_at', 'datetime_immutable');
            $series->setPrimaryKey(['id']);
            $series->addUniqueIndex(['prefix', 'year'], 'uniq_invoice_series_prefix_year');
        }

        if (!$schema->hasTable('invoice')) {
            return;
        }

        $invoice = $schema->getTable('invoice');

        if (!$invoice->hasColumn('customer_id')) {
            $invoice->addColumn('customer_id', 'bigint', ['notnull' => false]);
        }

        if (!$invoice->hasColumn('invoice_series_id')) {
            $invoice->addColumn('invoice_series_id', 'bigint', ['notnull' => false]);
        }

        if (!$invoice->hasIndex('IDX_906517449395C3F3')) {
            $invoice->addIndex(['customer_id'], 'IDX_906517449395C3F3');
        }

        if (!$invoice->hasIndex('IDX_9065174437D7C000')) {
            $invoice->addIndex(['invoice_series_id'], 'IDX_9065174437D7C000');
        }

        if (!$invoice->hasForeignKey('FK_906517449395C3F3')) {
            $invoice->addForeignKeyConstraint('customer', ['customer_id'], ['id'], [], 'FK_906517449395C3F3');
        }

        if (!$invoice->hasForeignKey('FK_9065174437D7C000')) {
            $invoice->addForeignKeyConstraint('invoice_series', ['invoice_series_id'], ['id'], [], 'FK_9065174437D7C000');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('invoice')) {
            $invoice = $schema->getTable('invoice');

            if ($invoice->hasForeignKey('FK_906517449395C3F3')) {
                $invoice->removeForeignKey('FK_906517449395C3F3');
            }

            if ($invoice->hasForeignKey('FK_9065174437D7C000')) {
                $invoice->removeForeignKey('FK_9065174437D7C000');
            }

            if ($invoice->hasIndex('IDX_906517449395C3F3')) {
                $invoice->dropIndex('IDX_906517449395C3F3');
            }

            if ($invoice->hasIndex('IDX_9065174437D7C000')) {
                $invoice->dropIndex('IDX_9065174437D7C000');
            }

            if ($invoice->hasColumn('customer_id')) {
                $invoice->dropColumn('customer_id');
            }

            if ($invoice->hasColumn('invoice_series_id')) {
                $invoice->dropColumn('invoice_series_id');
            }
        }

        if ($schema->hasTable('invoice_series')) {
            $schema->dropTable('invoice_series');
        }

        if ($schema->hasTable('customer')) {
            $schema->dropTable('customer');
        }
    }
}
