<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The slot texts delivery ledger (F24): one row per (edition, priant) who
 * received the slot texts e-mail, so the command
 * app:chain:send-slot-texts is idempotent.
 */
final class Version20261111121921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create slot_texts_sent, the slot texts e-mail delivery ledger';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE slot_texts_sent (id UUID NOT NULL, edition_id UUID NOT NULL, priant_id UUID NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX slot_texts_sent_edition_priant_unique ON slot_texts_sent (edition_id, priant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE slot_texts_sent');
    }
}
