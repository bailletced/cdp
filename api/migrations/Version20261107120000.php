<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop the meditation hints of the texts: the product no longer needs them.
 * The hints data is abandoned on purpose, nothing reads it anymore.
 */
final class Version20261107120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop text.hints (meditation hints removed from the product)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE text DROP COLUMN hints');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE text ADD COLUMN hints TEXT DEFAULT NULL');
    }
}
