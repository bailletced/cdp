<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for F02-T09: rememberme_token table for Doctrine token provider.
 */
final class Version20261017210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table rememberme_token pour le remember-me (DoctrineTokenProvider)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rememberme_token (series VARCHAR(88) NOT NULL PRIMARY KEY, value VARCHAR(88) NOT NULL, lastUsed TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, class VARCHAR(100) DEFAULT \'\' NOT NULL, username VARCHAR(200) NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS rememberme_token');
    }
}
