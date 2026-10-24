<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Symfony\Session;

use App\Shared\Infrastructure\Symfony\Session\DoctrineSessionPdoFactory;
use App\Shared\Infrastructure\Symfony\Session\ReconnectingPdoSessionHandler;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Integration tests for ReconnectingPdoSessionHandler.
 * Done when: session operations keep working after the connection they use is
 * killed server-side — the production failure mode of an idle connection
 * dropped by the network while the FrankenPHP worker keeps the handler alive.
 */
final class ReconnectingPdoSessionHandlerTest extends WebTestCase
{
    public function testSessionOperationsSurviveTheLossOfTheirDatabaseConnection(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);

        $handler = new ReconnectingPdoSessionHandler(
            new DoctrineSessionPdoFactory($connection),
            ['db_table' => 'sessions', 'lock_mode' => 0],
        );

        $this->assertTrue($handler->open('', 'test-session'));

        $sessionId = 'reconnect-'.Uuid::v7()->toRfc4122();
        $this->assertTrue($handler->write($sessionId, 'session-payload'));
        $this->assertSame('session-payload', $handler->read($sessionId));

        try {
            // Kill every application backend except this test's own connection:
            // the session handler's connection dies, like an idle connection
            // dropped by the network in production. The filter runs inside the
            // subquery so the projection can never call pg_terminate_backend()
            // for this connection's own backend: PostgreSQL does not guarantee
            // the evaluation order of WHERE quals.
            $terminated = (int) $connection->fetchOne(
                'SELECT count(*) FROM ('
                .'SELECT pg_terminate_backend(pid) AS terminated FROM pg_stat_activity'
                .' WHERE usename = current_user AND pid <> pg_backend_pid()'
                .') AS killed_backends',
            );
            $this->assertGreaterThanOrEqual(1, $terminated);

            // The handler reconnects transparently: reads and writes succeed.
            $this->assertSame('session-payload', $handler->read($sessionId));
            $this->assertTrue($handler->write($sessionId, 'updated-payload'));
            $this->assertSame('updated-payload', $handler->read($sessionId));
        } finally {
            $handler->destroy($sessionId);
        }
    }
}
