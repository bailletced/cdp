<?php

declare(strict_types=1);

namespace App\Tests\Behat\Session;

use App\Tests\Behat\BehatKernelTrait;
use Behat\Behat\Context\Context;
use Behat\Step\When;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ConnectionLost;

/**
 * Behat context for session storage resilience.
 */
final class SessionContext implements Context
{
    use BehatKernelTrait;

    #[When('the application database connections are terminated')]
    public function theApplicationDatabaseConnectionsAreTerminated(): void
    {
        $connection = $this->state()->entityManager?->getConnection();

        if (null === $connection) {
            throw new \RuntimeException('No database connection available.');
        }

        // Kill from a dedicated connection. In production a network blip drops
        // every idle connection, the application's included — and killing from
        // the application's own connection would spare the session handler,
        // which shares that connection in the current wiring, because
        // pg_backend_pid() excludes the connection running the query. The filter
        // runs inside the subquery so the projection can never call
        // pg_terminate_backend() for the killer's own backend: PostgreSQL does
        // not guarantee the evaluation order of WHERE quals.
        $killer = DriverManager::getConnection($connection->getParams());

        try {
            $terminated = (int) $killer->fetchOne(
                'SELECT count(*) FROM ('
                .'SELECT pg_terminate_backend(pid) AS terminated FROM pg_stat_activity'
                .' WHERE usename = current_user AND pid <> pg_backend_pid()'
                .') AS killed_backends',
            );
        } finally {
            $killer->close();
        }

        if (0 === $terminated) {
            throw new \RuntimeException('No database connection was terminated: the session handler connection is missing.');
        }
    }

    #[When('the application database connection is used again')]
    public function theApplicationDatabaseConnectionIsUsedAgain(): void
    {
        $connection = $this->state()->entityManager?->getConnection();

        if (null === $connection) {
            throw new \RuntimeException('No database connection available.');
        }

        // DBAL reconnects lazily: the first query after the loss fails and
        // closes the dead connection, the next one opens a fresh one. Use the
        // connection once to absorb the failure, then check it works again.
        // This mirrors production, where DBAL replaces the PDO instance on
        // reconnect while a session handler sharing it keeps the dead one.
        try {
            $connection->fetchOne('SELECT 1');
        } catch (ConnectionLost) {
            // Expected: the connection was terminated.
        }

        try {
            $connection->fetchOne('SELECT 1');
        } catch (\Throwable $exception) {
            throw new \RuntimeException('The application database connection did not recover.', previous: $exception);
        }
    }
}
