<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for F02-T03: organizer table.
 */
final class Version20261017180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table organizer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organizer (id UUID NOT NULL, account_id UUID NOT NULL, added_by UUID NOT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ORGANIZER_ACCOUNT_ID ON organizer (account_id)');
        $this->addSql('CREATE INDEX IDX_ORGANIZER_ADDED_BY ON organizer (added_by)');
        $this->addSql('CREATE INDEX IDX_ORGANIZER_ADDED_AT ON organizer (added_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS organizer');
    }
}
