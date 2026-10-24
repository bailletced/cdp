<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Session\Fixture;

/**
 * Fake PDO bound to an exception: prepare() throws it, simulating a database
 * operation on a dead connection. Never connects anywhere.
 */
final class FakePdo extends \PDO
{
    public function __construct(private readonly ?\PDOException $exception)
    {
    }

    #[\Override]
    public function getAttribute(int $attribute): mixed
    {
        return match ($attribute) {
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DRIVER_NAME => 'pgsql',
            default => null,
        };
    }

    #[\Override]
    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        $exception = $this->exception ?? new \PDOException('FakePdo is not connected to any database');

        throw $exception;
    }
}
