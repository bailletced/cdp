<?php

declare(strict_types=1);

namespace App\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;

interface PriantRepositoryInterface
{
    /**
     * Find a priant by its ID.
     */
    public function findById(PriantId $id): ?Priant;

    /**
     * Find a priant by its email (case-insensitive, RG-IN-2).
     */
    public function findByEmail(Email $email): ?Priant;

    /**
     * Add a priant to the repository. The aggregate is persisted when the
     * surrounding transaction flushes; the repository never flushes itself.
     */
    public function add(Priant $priant): void;
}
