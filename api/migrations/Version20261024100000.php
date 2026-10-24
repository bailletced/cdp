<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F10-T01 : table device_pass pour le pass appareil (BC Access, RG-IN-6).
 *
 * Seul le hash SHA-256 du jeton est stocké (unique). Les inscriptions
 * couvertes sont une liste JSON d'UUID : Access ne référence pas les tables
 * de Chain, et les droits sont recalculés à chaque requête.
 */
final class Version20261024100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F10-T01 : table device_pass (pass appareil, BC Access)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE device_pass (
            id UUID NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            inscription_ids JSON NOT NULL,
            expires_at TIMESTAMPTZ NOT NULL,
            last_seen_at TIMESTAMPTZ NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX device_pass_token_hash_unique ON device_pass (token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE device_pass');
    }
}
