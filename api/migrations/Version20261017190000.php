<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for F02-T06: sessions table for PdoSessionHandler.
 */
final class Version20261017190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table sessions pour PostgreSQL session handler';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL PRIMARY KEY, sess_data TEXT, sess_lifetime INTEGER, sess_time INTEGER)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sessions');
    }
}
