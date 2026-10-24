<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Session;

/**
 * Creates the PDO connection used by the session handler.
 *
 * The session handler must never share the PDO instance captured from the
 * Doctrine connection: DBAL closes and replaces that instance when it
 * reconnects, which would leave the session handler with a dead connection.
 */
interface SessionPdoFactory
{
    public function create(): \PDO;
}
