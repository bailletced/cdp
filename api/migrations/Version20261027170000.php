<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * F15-T01 : table cache_items du pool de cache DBAL (cache.rate_limiter) qui
 * porte les compteurs anti-abus, partagés entre réplicas (§6.7, T18).
 *
 * Même schéma que celui de Symfony\Component\Cache\Adapter\DoctrineDbalAdapter
 * sur PostgreSQL.
 */
final class Version20261027170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'F15-T01 : table cache_items (compteurs anti-abus)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cache_items (
            item_id VARCHAR(255) NOT NULL,
            item_data BYTEA NOT NULL,
            item_lifetime INT DEFAULT NULL,
            item_time INT NOT NULL,
            PRIMARY KEY(item_id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cache_items');
    }
}
