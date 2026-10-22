<?php

declare(strict_types=1);

namespace App\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;

interface EditionRepositoryInterface
{
    public function findById(EditionId $id): ?Edition;

    public function findBySlotId(SlotId $slotId): ?Edition;

    /**
     * @return Edition[]
     */
    public function findAll(): array;

    public function add(Edition $edition): void;

    public function remove(Edition $edition): void;
}
