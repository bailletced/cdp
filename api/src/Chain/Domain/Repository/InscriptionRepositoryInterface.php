<?php

declare(strict_types=1);

namespace App\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\ValueObject\EditionId;

interface InscriptionRepositoryInterface
{
    /**
     * Add an inscription to the repository. Uniqueness of (slot, priant) is
     * enforced up to the database by the unique index (RG-IN-3): a duplicate
     * raises an AlreadyRegisteredException. The aggregate is persisted when
     * the surrounding transaction flushes; the repository never flushes itself.
     *
     * @throws \App\Chain\Domain\Exception\AlreadyRegisteredException on duplicate (slot, priant)
     */
    public function add(Inscription $inscription): void;

    /**
     * Number of inscriptions of the given edition (RG-ED-3: the start time
     * is locked as soon as the first inscription exists).
     */
    public function countByEdition(EditionId $editionId): int;
}
