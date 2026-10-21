<?php

declare(strict_types=1);

namespace App\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\TextId;

/**
 * Repository contract for the Text aggregate root.
 *
 * Declared for the shared TextRepositoryTestCase contract suite; the
 * in-memory implementation satisfies it now, the Doctrine one lands in
 * F06-T04.
 */
interface TextRepositoryInterface
{
    public function findById(TextId $id): ?Text;

    /**
     * @return Text[]
     */
    public function findAll(): array;

    public function add(Text $text): void;

    public function remove(Text $text): void;
}
