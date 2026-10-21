<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F05-T22: database default for edition.version.
 *
 * The version column backs the #[ORM\Version] optimistic lock: Doctrine omits
 * it from INSERT statements and reads the value back afterwards, so the column
 * needs a database default (the schema tool generates one). Without it,
 * creating an edition fails with a not-null violation — caught by the E2E
 * journey (the test database is built with the schema tool and never showed
 * the defect).
 */
final class Version20261021133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F05-T22 : edition.version — default 1 for the optimistic-lock insert';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE edition ALTER COLUMN version SET DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE edition ALTER COLUMN version DROP DEFAULT');
    }
}
