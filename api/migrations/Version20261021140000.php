<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F06-T04: table text pour la bibliothèque (BC Chain).
 *
 * Agrégat Text : titre, source, corps (texte riche nettoyé) et pistes de
 * méditation facultatives. Pas de clé étrangère depuis slot.text_id : la
 * cohérence des attributions est une règle de domaine (RG-TX-3), pas une
 * contrainte de base.
 */
final class Version20261021140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F06-T04 : table text (bibliothèque, BC Chain)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE text (
            id UUID NOT NULL,
            title VARCHAR(120) NOT NULL,
            source VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            hints TEXT DEFAULT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE text');
    }
}
