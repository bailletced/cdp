<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour F00-T18 : Transports Messenger Doctrine.
 * Crée les tables pour async_transactional, async_bulk et failed.
 */
final class Version20261017160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables Messenger pour async_transactional, async_bulk et failed';
    }

    public function up(Schema $schema): void
    {
        // Table pour async_transactional
        $this->addSql('CREATE TABLE messenger_messages_async_transactional (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_ASYNC_TRANSACTIONAL_QUEUE_NAME ON messenger_messages_async_transactional (queue_name)');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_ASYNC_TRANSACTIONAL_AVAILABLE_AT ON messenger_messages_async_transactional (available_at)');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_ASYNC_TRANSACTIONAL_DELIVERED_AT ON messenger_messages_async_transactional (delivered_at)');

        // Table pour async_bulk
        $this->addSql('CREATE TABLE messenger_messages_async_bulk (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_ASYNC_BULK_QUEUE_NAME ON messenger_messages_async_bulk (queue_name)');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_ASYNC_BULK_AVAILABLE_AT ON messenger_messages_async_bulk (available_at)');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_ASYNC_BULK_DELIVERED_AT ON messenger_messages_async_bulk (delivered_at)');

        // Table pour failed
        $this->addSql('CREATE TABLE messenger_messages_failed (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_FAILED_QUEUE_NAME ON messenger_messages_failed (queue_name)');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_FAILED_AVAILABLE_AT ON messenger_messages_failed (available_at)');
        $this->addSql('CREATE INDEX IDX_MESSENGER_MESSAGES_FAILED_DELIVERED_AT ON messenger_messages_failed (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS messenger_messages_failed');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages_async_bulk');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages_async_transactional');
    }
}
