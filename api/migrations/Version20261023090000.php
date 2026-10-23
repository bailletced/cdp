<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F09-T03: table priant pour le domaine Participation (BC Chain).
 *
 * Agrégat Priant : e-mail facultatif (absent pour un priant saisi par un
 * organisateur, F16), nom affiché, consentement et marqueurs RGPD.
 *
 * RG-IN-2 : un e-mail = un priant — index unique partiel sur lower(email),
 * insensible à la casse, qui autorise plusieurs priants sans e-mail.
 */
final class Version20261023090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F09-T03 : table priant (domaine Participation, BC Chain)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE priant (
            id UUID NOT NULL,
            email VARCHAR(254) DEFAULT NULL,
            display_name VARCHAR(80) NOT NULL,
            consent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            unsubscribed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            erased_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX priant_email_unique ON priant (lower(email)) WHERE email IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE priant');
    }
}
