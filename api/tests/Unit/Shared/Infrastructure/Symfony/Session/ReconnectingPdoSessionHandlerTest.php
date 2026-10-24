<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Session;

use App\Shared\Infrastructure\Symfony\Session\ReconnectingPdoSessionHandler;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Session\Fixture\FakeSessionPdoFactory;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReconnectingPdoSessionHandler.
 * Done when: a lost database connection triggers exactly one reconnect and a
 * transparent retry, and any other database error is rethrown as-is.
 */
final class ReconnectingPdoSessionHandlerTest extends TestCase
{
    private const array OPTIONS = ['db_table' => 'sessions', 'lock_mode' => 0];

    public function testReadReconnectsAndRetriesOnceWhenTheConnectionIsLost(): void
    {
        $lost = self::connectionLostException('first connection');
        $retry = self::connectionLostException('retry connection');
        $factory = new FakeSessionPdoFactory($lost, $retry);

        $handler = new ReconnectingPdoSessionHandler($factory, self::OPTIONS);
        self::assertSame(1, $factory->createdCount());

        try {
            $handler->read('session-id');
            self::fail('The retried read should fail on the fake PDO');
        } catch (\PDOException $caught) {
            // The retry ran on the fresh handler: the second PDO threw.
            self::assertSame($retry, $caught);
        }

        self::assertSame(2, $factory->createdCount());
    }

    public function testItDoesNotReconnectOnOtherDatabaseErrors(): void
    {
        $error = new \PDOException('SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "sessions" does not exist');
        $error->errorInfo = ['42P01', 7, 'ERROR: relation "sessions" does not exist'];
        $factory = new FakeSessionPdoFactory($error);

        $handler = new ReconnectingPdoSessionHandler($factory, self::OPTIONS);

        try {
            $handler->read('session-id');
            self::fail('The database error should be rethrown as-is');
        } catch (\PDOException $caught) {
            self::assertSame($error, $caught);
        }

        self::assertSame(1, $factory->createdCount());
    }

    public function testValidateIdDefersTheFailureToReadWithoutReconnecting(): void
    {
        $lost = self::connectionLostException('first connection');
        $retry = self::connectionLostException('retry connection');
        $factory = new FakeSessionPdoFactory($lost, $retry);

        $handler = new ReconnectingPdoSessionHandler($factory, self::OPTIONS);

        // AbstractSessionHandler::validateId() catches the database error and
        // keeps the session id ('' !== false): the stored error is rethrown by
        // the next read(), where this handler reconnects.
        self::assertTrue($handler->validateId('session-id'));
        self::assertSame(1, $factory->createdCount());

        try {
            $handler->read('session-id');
            self::fail('read() should rethrow the stored connection error after one retry');
        } catch (\PDOException $caught) {
            self::assertSame($retry, $caught);
        }

        self::assertSame(2, $factory->createdCount());
    }

    private static function connectionLostException(string $connection): \PDOException
    {
        $exception = new \PDOException('SQLSTATE[HY000]: General error: 7 server closed the connection unexpectedly ('.$connection.')');
        $exception->errorInfo = ['HY000', 7, 'server closed the connection unexpectedly ('.$connection.')'];

        return $exception;
    }
}
