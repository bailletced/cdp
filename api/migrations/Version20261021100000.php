<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F05-T10: tables edition et slot pour le BC Chain.
 *
 * - edition : agrégat Edition (titre, présentation, startsAt, opensAt, lifecycle, version).
 * - slot    : entité interne (rank, starts_at, ends_at, text_id), 48 créneaux par édition.
 */
final class Version20261021100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F05-T10 : tables edition et slot (BC Chain)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE edition (
            id UUID NOT NULL,
            title VARCHAR(120) NOT NULL,
            presentation TEXT DEFAULT NULL,
            starts_at TIMESTAMPTZ NOT NULL,
            opens_at TIMESTAMPTZ NOT NULL,
            lifecycle VARCHAR(20) NOT NULL,
            scheduled_at TIMESTAMPTZ DEFAULT NULL,
            version INT NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE slot (
            id UUID NOT NULL,
            edition_id UUID NOT NULL,
            rank SMALLINT NOT NULL,
            starts_at TIMESTAMPTZ NOT NULL,
            ends_at TIMESTAMPTZ NOT NULL,
            text_id UUID DEFAULT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX slot_edition_rank_idx ON slot (edition_id, rank)');
        $this->addSql('CREATE UNIQUE INDEX slot_edition_rank_unique ON slot (edition_id, rank)');
        $this->addSql('CREATE INDEX slot_edition_id_idx ON slot (edition_id)');
        $this->addSql('ALTER TABLE slot ADD CONSTRAINT FK_SLOT_EDITION FOREIGN KEY (edition_id) REFERENCES edition (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE slot');
        $this->addSql('DROP TABLE edition');
    }
}
