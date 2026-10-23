<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F09-T06: table inscription pour le domaine Participation (BC Chain).
 *
 * Agrégat Inscription : un priant sur un créneau. L'unicité du couple
 * (slot, priant) est garantie en base par l'index unique
 * inscription_slot_priant_unique (RG-IN-3).
 *
 * Les références vers edition, slot et priant sont de simples colonnes UUID
 * sans association ORM (même approche que Slot::$textId) : il n'y a donc pas
 * de contrainte de clé étrangère, l'intégrité du graphe reste du ressort du
 * domaine.
 */
final class Version20261023090100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F09-T06 : table inscription (domaine Participation, BC Chain)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE inscription (
            id UUID NOT NULL,
            edition_id UUID NOT NULL,
            slot_id UUID NOT NULL,
            priant_id UUID NOT NULL,
            origin VARCHAR(10) NOT NULL,
            version INT DEFAULT 1 NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX inscription_slot_priant_unique ON inscription (slot_id, priant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inscription');
    }
}
