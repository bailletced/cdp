<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Integration test for the PostgreSQL session storage (F02-T06).
 * Done when: the session is shared between two PHP processes, and the
 * handler persists the binary data of a serialized security token.
 */
final class SessionIntegrationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    public function testSessionHandlerIsPdo(): void
    {
        $handler = self::getContainer()->get('session.handler.pdo');

        $this->assertInstanceOf(PdoSessionHandler::class, $handler);
    }

    public function testPdoConnectionServiceExists(): void
    {
        $container = self::getContainer();

        $this->assertTrue($container->has('session.pdo_connection'));

        $pdo = $container->get('session.pdo_connection');
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    public function testSessionHandlerConfiguration(): void
    {
        $handler = self::getContainer()->get('session.handler.pdo');

        $this->assertInstanceOf(PdoSessionHandler::class, $handler);
    }

    /**
     * Regression test (F02-T20): an authenticated session stores a serialized
     * security token, which is binary data — PHP's serialize() encodes private
     * and protected property names with null bytes. The sess_data column must
     * be a bytea: a PostgreSQL text column rejects the 0x00 byte (SQLSTATE 22021).
     */
    public function testSessionHandlerPersistsBinarySessionData(): void
    {
        $handler = self::getContainer()->get('session.handler.pdo');
        \assert($handler instanceof PdoSessionHandler);

        $sessionId = 'regression-'.Uuid::v7()->toRfc4122();
        $data = serialize(new SessionDataWithPrivateProperty('token'));

        // Sanity check: the payload really is binary
        $this->assertStringContainsString("\0", $data);

        try {
            $handler->open('', 'test-session');
            $this->assertTrue($handler->write($sessionId, $data));
            $this->assertSame($data, $handler->read($sessionId));
        } finally {
            $handler->destroy($sessionId);
        }
    }
}
