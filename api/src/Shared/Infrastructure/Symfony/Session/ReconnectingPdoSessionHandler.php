<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * Decorates PdoSessionHandler with a transparent reconnect.
 *
 * In FrankenPHP worker mode the handler outlives its PDO connection: when the
 * network drops the idle connection, the next session operation fails with a
 * PDOException. This decorator rebuilds the inner handler on a fresh PDO and
 * retries the operation once. Session operations are idempotent (SELECT,
 * upsert, DELETE) and the handler runs with lock_mode 0, so a single retry is
 * safe.
 */
final class ReconnectingPdoSessionHandler implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    private PdoSessionHandler $handler;

    private bool $opened = false;

    private string $savePath = '';

    private string $sessionName = '';

    /**
     * @param array<string, mixed> $options PdoSessionHandler options (db_table, lock_mode, ...)
     */
    public function __construct(
        private readonly SessionPdoFactory $pdoFactory,
        private readonly array $options = [],
    ) {
        $this->handler = $this->createHandler();
    }

    #[\Override]
    public function open(string $savePath, string $sessionName): bool
    {
        $this->savePath = $savePath;
        $this->sessionName = $sessionName;
        $this->opened = true;

        return $this->withReconnect(static fn (PdoSessionHandler $handler): bool => $handler->open($savePath, $sessionName));
    }

    #[\Override]
    public function close(): bool
    {
        return $this->withReconnect(static fn (PdoSessionHandler $handler): bool => $handler->close());
    }

    #[\Override]
    public function read(string $sessionId): string|false
    {
        return $this->withReconnect(static fn (PdoSessionHandler $handler): string|false => $handler->read($sessionId));
    }

    #[\Override]
    public function write(string $sessionId, string $data): bool
    {
        return $this->withReconnect(static fn (PdoSessionHandler $handler): bool => $handler->write($sessionId, $data));
    }

    #[\Override]
    public function destroy(string $sessionId): bool
    {
        return $this->withReconnect(static fn (PdoSessionHandler $handler): bool => $handler->destroy($sessionId));
    }

    #[\Override]
    public function gc(int $maxlifetime): int|false
    {
        return $this->withReconnect(static fn (PdoSessionHandler $handler): int|false => $handler->gc($maxlifetime));
    }

    #[\Override]
    public function validateId(string $sessionId): bool
    {
        // No reconnect here: AbstractSessionHandler::validateId() swallows the
        // database error and keeps the session id. The stored error is rethrown
        // by the next read(), where the reconnect happens.
        return $this->handler->validateId($sessionId);
    }

    #[\Override]
    public function updateTimestamp(string $sessionId, string $data): bool
    {
        return $this->withReconnect(static fn (PdoSessionHandler $handler): bool => $handler->updateTimestamp($sessionId, $data));
    }

    /**
     * @template T
     *
     * @param \Closure(PdoSessionHandler): T $operation
     *
     * @return T
     */
    private function withReconnect(\Closure $operation): mixed
    {
        try {
            return $operation($this->handler);
        } catch (\PDOException $exception) {
            if (!self::isConnectionLost($exception)) {
                throw $exception;
            }

            $this->handler = $this->createHandler();

            return $operation($this->handler);
        }
    }

    private function createHandler(): PdoSessionHandler
    {
        $handler = new PdoSessionHandler($this->pdoFactory->create(), $this->options);

        if ($this->opened) {
            // A rebuilt handler is a fresh instance: AbstractSessionHandler
            // rejects operations until open() has been called on it.
            $handler->open($this->savePath, $this->sessionName);
        }

        return $handler;
    }

    private static function isConnectionLost(\PDOException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        if (!\is_array($errorInfo)) {
            return false;
        }

        $sqlState = $errorInfo[0] ?? null;
        $driverCode = $errorInfo[1] ?? null;

        // SQLSTATE class 08: connection exceptions.
        if (\is_string($sqlState) && str_starts_with($sqlState, '08')) {
            return true;
        }

        // PostgreSQL driver code 7 (SQLSTATE HY000): server closed the connection.
        if ('HY000' === $sqlState && 7 === $driverCode) {
            return true;
        }

        $message = $exception->getMessage();

        return str_contains($message, 'server closed the connection') || str_contains($message, 'no connection to the server');
    }
}
