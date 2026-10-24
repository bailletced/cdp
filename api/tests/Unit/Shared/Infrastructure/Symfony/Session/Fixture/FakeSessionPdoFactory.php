<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Session\Fixture;

use App\Shared\Infrastructure\Symfony\Session\SessionPdoFactory;

/**
 * Fake session PDO factory: returns one FakePdo per queued exception, in order.
 */
final class FakeSessionPdoFactory implements SessionPdoFactory
{
    /** @var list<FakePdo> */
    private array $queued = [];

    /** @var list<FakePdo> */
    private array $created = [];

    public function __construct(\PDOException ...$exceptions)
    {
        foreach ($exceptions as $exception) {
            $this->queued[] = new FakePdo($exception);
        }
    }

    #[\Override]
    public function create(): \PDO
    {
        $pdo = array_shift($this->queued)
            ?? new FakePdo(new \PDOException('FakeSessionPdoFactory: no fake PDO queued for this create() call'));

        $this->created[] = $pdo;

        return $pdo;
    }

    public function createdCount(): int
    {
        return \count($this->created);
    }
}
