<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial invoicing domain schema with immutable money storage, status transitions, rectifications, and VeriFactu chain fields.';
    }

    public function up(Schema $schema): void
    {
        $invoice = $schema->createTable('invoice');
        $invoice->addColumn('id', 'bigint', ['autoincrement' => true]);
        $invoice->addColumn('invoice_number', 'string', ['length' => 50]);
        $invoice->addColumn('issued_at', 'date_immutable');
        $invoice->addColumn('status', 'string', ['length' => 16]);
        $invoice->addColumn('currency', 'string', ['length' => 3]);
        $invoice->addColumn('subtotal_cents', 'bigint');
        $invoice->addColumn('tax_total_cents', 'bigint');
        $invoice->addColumn('grand_total_cents', 'bigint');
        $invoice->addColumn('previous_invoice_hash', 'string', ['length' => 64, 'notnull' => false]);
        $invoice->addColumn('current_invoice_hash', 'string', ['length' => 64, 'notnull' => false]);
        $invoice->addColumn('finalized_at', 'datetime_immutable', ['notnull' => false]);
        $invoice->addColumn('recipient_status_updated_at', 'datetime_immutable', ['notnull' => false]);
        $invoice->addColumn('recipient_status_note', 'text', ['notnull' => false]);
        $invoice->addColumn('created_at', 'datetime_immutable');
        $invoice->addColumn('updated_at', 'datetime_immutable');
        $invoice->setPrimaryKey(['id']);
        $invoice->addUniqueIndex(['invoice_number'], 'uniq_invoice_number');
        $invoice->addUniqueIndex(['current_invoice_hash'], 'uniq_invoice_current_hash');
        $invoice->addIndex(['status', 'issued_at'], 'idx_invoice_status_issued_at');

        $invoiceItem = $schema->createTable('invoice_item');
        $invoiceItem->addColumn('id', 'bigint', ['autoincrement' => true]);
        $invoiceItem->addColumn('invoice_id', 'bigint');
        $invoiceItem->addColumn('description', 'string', ['length' => 255]);
        $invoiceItem->addColumn('quantity', 'bigint');
        $invoiceItem->addColumn('unit_price_cents', 'bigint');
        $invoiceItem->addColumn('tax_rate_basis_points', 'integer');
        $invoiceItem->addColumn('line_subtotal_cents', 'bigint');
        $invoiceItem->addColumn('line_tax_cents', 'bigint');
        $invoiceItem->addColumn('line_total_cents', 'bigint');
        $invoiceItem->setPrimaryKey(['id']);
        $invoiceItem->addIndex(['invoice_id'], 'idx_invoice_item_invoice_id');
        $invoiceItem->addForeignKeyConstraint('invoice', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        $transition = $schema->createTable('invoice_status_transition');
        $transition->addColumn('id', 'bigint', ['autoincrement' => true]);
        $transition->addColumn('invoice_id', 'bigint');
        $transition->addColumn('from_status', 'string', ['length' => 16, 'notnull' => false]);
        $transition->addColumn('to_status', 'string', ['length' => 16]);
        $transition->addColumn('changed_at', 'datetime_immutable');
        $transition->addColumn('note', 'text', ['notnull' => false]);
        $transition->setPrimaryKey(['id']);
        $transition->addIndex(['invoice_id', 'changed_at'], 'idx_invoice_status_transition_invoice_id_changed_at');
        $transition->addForeignKeyConstraint('invoice', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        $rectification = $schema->createTable('invoice_rectification');
        $rectification->addColumn('id', 'bigint', ['autoincrement' => true]);
        $rectification->addColumn('original_invoice_id', 'bigint');
        $rectification->addColumn('rectification_number', 'string', ['length' => 50]);
        $rectification->addColumn('reason', 'text');
        $rectification->addColumn('adjustment_subtotal_cents', 'bigint');
        $rectification->addColumn('adjustment_tax_cents', 'bigint');
        $rectification->addColumn('adjustment_total_cents', 'bigint');
        $rectification->addColumn('created_at', 'datetime_immutable');
        $rectification->setPrimaryKey(['id']);
        $rectification->addUniqueIndex(['rectification_number'], 'uniq_rectification_number');
        $rectification->addIndex(['original_invoice_id'], 'idx_rectification_original_invoice_id');
        $rectification->addForeignKeyConstraint('invoice', ['original_invoice_id'], ['id'], ['onDelete' => 'RESTRICT']);

    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('invoice_rectification');
        $schema->dropTable('invoice_status_transition');
        $schema->dropTable('invoice_item');
        $schema->dropTable('invoice');
    }
}
