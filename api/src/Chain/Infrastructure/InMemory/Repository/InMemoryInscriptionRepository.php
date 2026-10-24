<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\InMemory\Repository;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Exception\AlreadyRegisteredException;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;

/**
 * In-memory implementation of InscriptionRepositoryInterface for testing purposes.
 *
 * Simulates the database unique index on (slot_id, priant_id) (RG-IN-3).
 */
final class InMemoryInscriptionRepository implements InscriptionRepositoryInterface
{
    /**
     * @var array<string, Inscription> Keyed by "<slotId>|<priantId>"
     */
    private array $inscriptions = [];

    #[\Override]
    public function add(Inscription $inscription): void
    {
        $key = $this->key($inscription->getSlotId(), $inscription->getPriantId());

        if (isset($this->inscriptions[$key])) {
            throw new AlreadyRegisteredException();
        }

        $this->inscriptions[$key] = $inscription;
    }

    #[\Override]
    public function countByEdition(EditionId $editionId): int
    {
        $count = 0;
        foreach ($this->inscriptions as $inscription) {
            if ($inscription->getEditionId()->equals($editionId)) {
                ++$count;
            }
        }

        return $count;
    }

    #[\Override]
    public function findOwnedOrCovered(?PriantId $priantId, array $inscriptionIds): array
    {
        $found = [];
        foreach ($this->inscriptions as $inscription) {
            $owned = null !== $priantId && $inscription->getPriantId()->equals($priantId);
            $covered = array_any($inscriptionIds, static fn (InscriptionId $id): bool => $id->equals($inscription->getId()));

            if ($owned || $covered) {
                $found[] = $inscription;
            }
        }

        return $found;
    }

    private function key(SlotId $slotId, PriantId $priantId): string
    {
        return \sprintf('%s|%s', (string) $slotId, (string) $priantId);
    }
}
