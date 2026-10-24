<?php

declare(strict_types=1);

namespace App\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;

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

    /**
     * The inscriptions of a priant, plus the given ones (those covered by a
     * device pass), whatever their edition (F10-T05).
     *
     * @param list<InscriptionId> $inscriptionIds
     *
     * @return list<Inscription>
     */
    public function findOwnedOrCovered(?PriantId $priantId, array $inscriptionIds): array;

    public function findById(InscriptionId $id): ?Inscription;

    /**
     * Remove an inscription (F14-T05, RG-EP-1): its slot is free again. The
     * removal reaches the database when the surrounding transaction flushes.
     */
    public function remove(Inscription $inscription): void;
}
