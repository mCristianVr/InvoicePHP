<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the user_account table used by Symfony authentication.';
    }

    public function up(Schema $schema): void
    {
        $user = $schema->createTable('user_account');
        $user->addColumn('id', 'bigint', ['autoincrement' => true]);
        $user->addColumn('email', 'string', ['length' => 180]);
        $user->addColumn('roles', 'json');
        $user->addColumn('password', 'string');
        $user->addColumn('created_at', 'datetime_immutable');
        $user->setPrimaryKey(['id']);
        $user->addUniqueIndex(['email'], 'uniq_user_email');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('user_account');
    }
}
