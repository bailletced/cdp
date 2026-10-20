<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Application\Port\DatabaseHealth;
use Doctrine\DBAL\Connection;

/**
 * Doctrine implementation of DatabaseHealth (F03-T06).
 */
final class DoctrineDatabaseHealth implements DatabaseHealth
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function isUp(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function schemaStatus(): string
    {
        try {
            $result = $this->connection->fetchOne(
                "SELECT to_regclass('doctrine_migration_versions') IS NOT NULL"
            );

            return $result ? 'up-to-date' : 'no-migrations';
        } catch (\Throwable) {
            return 'unknown';
        }
    }
}
