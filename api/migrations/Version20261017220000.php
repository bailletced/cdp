<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for F02-T20: sessions.sess_data must be a bytea column.
 * Serialized session data is binary — PHP's serialize() encodes private and
 * protected property names with null bytes (e.g. the security token stored in
 * an authenticated session) — and a PostgreSQL text column rejects the 0x00
 * byte (SQLSTATE 22021). This matches PdoSessionHandler::createTable(), which
 * creates sess_data as BYTEA on PostgreSQL.
 */
final class Version20261017220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convertit sessions.sess_data en bytea (les données de session sont binaires)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sessions ALTER COLUMN sess_data TYPE bytea USING sess_data::bytea');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE sessions ALTER COLUMN sess_data TYPE text USING convert_from(sess_data, 'UTF8')");
    }
}
