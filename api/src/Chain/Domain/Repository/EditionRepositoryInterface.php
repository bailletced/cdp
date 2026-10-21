<?php

declare(strict_types=1);

namespace App\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\ValueObject\EditionId;

interface EditionRepositoryInterface
{
    public function findById(EditionId $id): ?Edition;

    /**
     * @return Edition[]
     */
    public function findAll(): array;

    public function add(Edition $edition): void;

    public function remove(Edition $edition): void;
}
