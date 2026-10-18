<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for F02-T02: account table with unique constraint on lower(email).
 */
final class Version20261017170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table account avec contrainte unique sur lower(email)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account (id UUID NOT NULL, email VARCHAR(254) NOT NULL, credentials_version INTEGER NOT NULL, priant_id UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACCOUNT_LOWER_EMAIL ON account (lower(email))');
        $this->addSql('CREATE INDEX IDX_ACCOUNT_EMAIL ON account (email)');
        $this->addSql('CREATE INDEX IDX_ACCOUNT_PRIANT_ID ON account (priant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS account');
    }
}
