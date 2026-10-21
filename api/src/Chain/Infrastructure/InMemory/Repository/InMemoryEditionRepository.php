<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\InMemory\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;

/**
 * In-memory implementation of EditionRepositoryInterface for testing purposes.
 */
final class InMemoryEditionRepository implements EditionRepositoryInterface
{
    /**
     * @var array<string, Edition> Keyed by EditionId string representation
     */
    private array $editions = [];

    public function findById(EditionId $id): ?Edition
    {
        return $this->editions[(string) $id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->editions);
    }

    public function add(Edition $edition): void
    {
        $this->editions[(string) $edition->getId()] = $edition;
    }

    public function remove(Edition $edition): void
    {
        unset($this->editions[(string) $edition->getId()]);
    }
}
