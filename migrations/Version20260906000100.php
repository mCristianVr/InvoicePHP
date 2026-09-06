<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds customer ownership, optional contact/location fields, and owner-scoped uniqueness for nif_cif.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('customer')) {
            return;
        }

        $customerRowCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM customer');

        $customer = $schema->getTable('customer');

        if (!$customer->hasColumn('owner_id')) {
            $customer->addColumn('owner_id', 'bigint', ['notnull' => false]);
        }

        if (!$customer->hasColumn('postal_code')) {
            $customer->addColumn('postal_code', 'string', ['length' => 20, 'notnull' => false]);
        }

        if (!$customer->hasColumn('city')) {
            $customer->addColumn('city', 'string', ['length' => 120, 'notnull' => false]);
        }

        if (!$customer->hasColumn('province')) {
            $customer->addColumn('province', 'string', ['length' => 120, 'notnull' => false]);
        }

        if ($customer->hasColumn('address')) {
            $customer->getColumn('address')->setNotnull(false);
        }

        if ($customer->hasColumn('email')) {
            $customer->getColumn('email')->setNotnull(false);
        }

        if ($customer->hasIndex('uniq_customer_nif_cif')) {
            $customer->dropIndex('uniq_customer_nif_cif');
        }

        if (!$customer->hasIndex('idx_customer_owner_name')) {
            $customer->addIndex(['owner_id', 'name'], 'idx_customer_owner_name');
        }

        if (!$customer->hasIndex('uniq_customer_owner_nif_cif')) {
            $customer->addUniqueIndex(['owner_id', 'nif_cif'], 'uniq_customer_owner_nif_cif');
        }

        if ($schema->hasTable('user_account') && !$customer->hasForeignKey('FK_CUSTOMER_OWNER')) {
            $customer->addForeignKeyConstraint('user_account', ['owner_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_CUSTOMER_OWNER');
        }

        if ($customerRowCount === 0) {
            $customer->getColumn('owner_id')->setNotnull(true);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('customer')) {
            return;
        }

        $customer = $schema->getTable('customer');

        if ($customer->hasForeignKey('FK_CUSTOMER_OWNER')) {
            $customer->removeForeignKey('FK_CUSTOMER_OWNER');
        }

        if ($customer->hasIndex('uniq_customer_owner_nif_cif')) {
            $customer->dropIndex('uniq_customer_owner_nif_cif');
        }

        if ($customer->hasIndex('idx_customer_owner_name')) {
            $customer->dropIndex('idx_customer_owner_name');
        }

        if (!$customer->hasIndex('uniq_customer_nif_cif')) {
            $customer->addUniqueIndex(['nif_cif'], 'uniq_customer_nif_cif');
        }

        if ($customer->hasColumn('owner_id')) {
            $customer->dropColumn('owner_id');
        }

        if ($customer->hasColumn('postal_code')) {
            $customer->dropColumn('postal_code');
        }

        if ($customer->hasColumn('city')) {
            $customer->dropColumn('city');
        }

        if ($customer->hasColumn('province')) {
            $customer->dropColumn('province');
        }

        if ($customer->hasColumn('address')) {
            $customer->getColumn('address')->setNotnull(true);
        }

        if ($customer->hasColumn('email')) {
            $customer->getColumn('email')->setNotnull(true);
        }
    }
}
