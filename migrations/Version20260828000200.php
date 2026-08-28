<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds integrity check constraints for invoice domain tables.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if (!($platform instanceof PostgreSQLPlatform || $platform instanceof AbstractMySQLPlatform)) {
            return;
        }

        $this->addSql("ALTER TABLE IF EXISTS invoice ADD CONSTRAINT chk_invoice_status CHECK (status IN ('DRAFT', 'SENT', 'PAID', 'OVERDUE', 'REJECTED'))");
        $this->addSql('ALTER TABLE IF EXISTS invoice ADD CONSTRAINT chk_invoice_subtotal_non_negative CHECK (subtotal_cents >= 0)');
        $this->addSql('ALTER TABLE IF EXISTS invoice ADD CONSTRAINT chk_invoice_tax_non_negative CHECK (tax_total_cents >= 0)');
        $this->addSql('ALTER TABLE IF EXISTS invoice ADD CONSTRAINT chk_invoice_total_match CHECK (grand_total_cents = subtotal_cents + tax_total_cents)');

        $this->addSql('ALTER TABLE IF EXISTS invoice_item ADD CONSTRAINT chk_invoice_item_quantity_positive CHECK (quantity > 0)');
        $this->addSql('ALTER TABLE IF EXISTS invoice_item ADD CONSTRAINT chk_invoice_item_unit_price_non_negative CHECK (unit_price_cents >= 0)');
        $this->addSql('ALTER TABLE IF EXISTS invoice_item ADD CONSTRAINT chk_invoice_item_tax_basis_points CHECK (tax_rate_basis_points >= 0 AND tax_rate_basis_points <= 10000)');
        $this->addSql('ALTER TABLE IF EXISTS invoice_item ADD CONSTRAINT chk_invoice_item_total_match CHECK (line_total_cents = line_subtotal_cents + line_tax_cents)');

        $this->addSql("ALTER TABLE IF EXISTS invoice_status_transition ADD CONSTRAINT chk_invoice_transition_from_status CHECK (from_status IS NULL OR from_status IN ('DRAFT', 'SENT', 'PAID', 'OVERDUE', 'REJECTED'))");
        $this->addSql("ALTER TABLE IF EXISTS invoice_status_transition ADD CONSTRAINT chk_invoice_transition_to_status CHECK (to_status IN ('DRAFT', 'SENT', 'PAID', 'OVERDUE', 'REJECTED'))");

        $this->addSql('ALTER TABLE IF EXISTS invoice_rectification ADD CONSTRAINT chk_rectification_total_match CHECK (adjustment_total_cents = adjustment_subtotal_cents + adjustment_tax_cents)');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if (!($platform instanceof PostgreSQLPlatform || $platform instanceof AbstractMySQLPlatform)) {
            return;
        }

        $this->addSql('ALTER TABLE IF EXISTS invoice_rectification DROP CONSTRAINT IF EXISTS chk_rectification_total_match');

        $this->addSql('ALTER TABLE IF EXISTS invoice_status_transition DROP CONSTRAINT IF EXISTS chk_invoice_transition_to_status');
        $this->addSql('ALTER TABLE IF EXISTS invoice_status_transition DROP CONSTRAINT IF EXISTS chk_invoice_transition_from_status');

        $this->addSql('ALTER TABLE IF EXISTS invoice_item DROP CONSTRAINT IF EXISTS chk_invoice_item_total_match');
        $this->addSql('ALTER TABLE IF EXISTS invoice_item DROP CONSTRAINT IF EXISTS chk_invoice_item_tax_basis_points');
        $this->addSql('ALTER TABLE IF EXISTS invoice_item DROP CONSTRAINT IF EXISTS chk_invoice_item_unit_price_non_negative');
        $this->addSql('ALTER TABLE IF EXISTS invoice_item DROP CONSTRAINT IF EXISTS chk_invoice_item_quantity_positive');

        $this->addSql('ALTER TABLE IF EXISTS invoice DROP CONSTRAINT IF EXISTS chk_invoice_total_match');
        $this->addSql('ALTER TABLE IF EXISTS invoice DROP CONSTRAINT IF EXISTS chk_invoice_tax_non_negative');
        $this->addSql('ALTER TABLE IF EXISTS invoice DROP CONSTRAINT IF EXISTS chk_invoice_subtotal_non_negative');
        $this->addSql('ALTER TABLE IF EXISTS invoice DROP CONSTRAINT IF EXISTS chk_invoice_status');
    }
}
