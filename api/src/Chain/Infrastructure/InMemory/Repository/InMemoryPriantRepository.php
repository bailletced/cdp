<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\InMemory\Repository;

use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;

/**
 * In-memory implementation of PriantRepositoryInterface for testing purposes.
 */
final class InMemoryPriantRepository implements PriantRepositoryInterface
{
    /**
     * @var array<string, Priant> Keyed by PriantId string representation
     */
    private array $priantsById = [];

    /**
     * @var array<string, Priant> Keyed by lowercase email; priants without email are not indexed
     */
    private array $priantsByEmail = [];

    #[\Override]
    public function findById(PriantId $id): ?Priant
    {
        return $this->priantsById[(string) $id] ?? null;
    }

    #[\Override]
    public function findByEmail(Email $email): ?Priant
    {
        return $this->priantsByEmail[mb_strtolower($email->value)] ?? null;
    }

    #[\Override]
    public function add(Priant $priant): void
    {
        $this->priantsById[(string) $priant->getId()] = $priant;

        $email = $priant->getEmail();
        if (null !== $email) {
            $this->priantsByEmail[mb_strtolower($email->value)] = $priant;
        }
    }
}
