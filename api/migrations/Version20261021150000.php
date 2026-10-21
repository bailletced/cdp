<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F06-T07: extension unaccent pour la recherche insensible aux accents dans
 * la bibliothèque de textes (AdminTextViewFinder).
 *
 * unaccent est une extension « trusted » (Postgres 13+) : l'utilisateur
 * applicatif, propriétaire de la base, peut la créer.
 */
final class Version20261021150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F06-T07 : extension unaccent (recherche insensible aux accents, bibliothèque de textes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION IF EXISTS unaccent');
    }
}
