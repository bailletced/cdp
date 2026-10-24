<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Session;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Creates a dedicated PDO connection for the session handler from the Doctrine
 * connection parameters, so the handler never shares the PDO instance that
 * DBAL may close and replace on reconnect.
 */
final class DoctrineSessionPdoFactory implements SessionPdoFactory
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[\Override]
    public function create(): \PDO
    {
        $pdo = DriverManager::getConnection($this->connection->getParams())->getNativeConnection();

        if (!$pdo instanceof \PDO) {
            throw new \LogicException(\sprintf('Expected a PDO connection, got "%s".', get_debug_type($pdo)));
        }

        return $pdo;
    }
}
