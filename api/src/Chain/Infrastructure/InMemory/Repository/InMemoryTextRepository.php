<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\InMemory\Repository;

use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\TextId;

/**
 * In-memory implementation of TextRepositoryInterface for testing purposes.
 */
final class InMemoryTextRepository implements TextRepositoryInterface
{
    /**
     * @var array<string, Text> Keyed by TextId string representation
     */
    private array $texts = [];

    public function findById(TextId $id): ?Text
    {
        return $this->texts[(string) $id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->texts);
    }

    public function add(Text $text): void
    {
        $this->texts[(string) $text->getId()] = $text;
    }

    public function remove(Text $text): void
    {
        unset($this->texts[(string) $text->getId()]);
    }
}
