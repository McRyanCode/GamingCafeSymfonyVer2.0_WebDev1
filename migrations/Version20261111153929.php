<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261111153929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at column to users table and backfill existing users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD created_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE users SET created_at = NOW() WHERE created_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP created_at');
    }
}
