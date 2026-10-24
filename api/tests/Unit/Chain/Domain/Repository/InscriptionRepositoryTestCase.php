<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Exception\AlreadyRegisteredException;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test case for InscriptionRepository implementations.
 * Concrete implementations should extend this class and implement getRepository().
 */
abstract class InscriptionRepositoryTestCase extends TestCase
{
    protected InscriptionRepositoryInterface $repository;

    /**
     * Get the repository instance to test.
     */
    abstract protected function getRepository(): InscriptionRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    public function testAddAndCountByEdition(): void
    {
        $editionId = new EditionId();
        $otherEditionId = new EditionId();

        $this->repository->add(new Inscription($editionId, new SlotId(), new PriantId()));
        $this->repository->add(new Inscription($editionId, new SlotId(), new PriantId()));
        $this->repository->add(new Inscription($otherEditionId, new SlotId(), new PriantId()));

        $this->assertSame(2, $this->repository->countByEdition($editionId));
        $this->assertSame(1, $this->repository->countByEdition($otherEditionId));
        $this->assertSame(0, $this->repository->countByEdition(new EditionId()));
    }

    public function testDuplicateSlotAndPriantIsRejected(): void
    {
        $editionId = new EditionId();
        $slotId = new SlotId();
        $priantId = new PriantId();

        $this->repository->add(new Inscription($editionId, $slotId, $priantId));

        $this->expectException(AlreadyRegisteredException::class);

        $this->repository->add(new Inscription($editionId, $slotId, $priantId));
    }

    public function testSamePriantOnDifferentSlotsIsAllowed(): void
    {
        $editionId = new EditionId();
        $priantId = new PriantId();

        $this->repository->add(new Inscription($editionId, new SlotId(), $priantId));
        $this->repository->add(new Inscription($editionId, new SlotId(), $priantId));

        $this->assertSame(2, $this->repository->countByEdition($editionId));
    }

    public function testSameSlotForDifferentPriantsIsAllowed(): void
    {
        $editionId = new EditionId();
        $slotId = new SlotId();

        $this->repository->add(new Inscription($editionId, $slotId, new PriantId()));
        $this->repository->add(new Inscription($editionId, $slotId, new PriantId()));

        $this->assertSame(2, $this->repository->countByEdition($editionId));
    }

    public function testItFindsTheInscriptionsOfAPriantAndThoseCoveredByAPass(): void
    {
        $editionId = new EditionId();
        $priantId = new PriantId();
        $owned = new Inscription($editionId, new SlotId(), $priantId);
        $covered = new Inscription($editionId, new SlotId(), new PriantId());
        $other = new Inscription($editionId, new SlotId(), new PriantId());
        foreach ([$owned, $covered, $other] as $inscription) {
            $this->repository->add($inscription);
        }

        $found = $this->repository->findOwnedOrCovered($priantId, [$covered->getId(), $owned->getId()]);

        self::assertEqualsCanonicalizing([$owned, $covered], $found);
        self::assertSame([$covered], $this->repository->findOwnedOrCovered(null, [$covered->getId()]));
        self::assertSame([], $this->repository->findOwnedOrCovered(null, []));
    }

    public function testItFindsAnInscriptionByItsIdAndRemovesIt(): void
    {
        $editionId = new EditionId();
        $slotId = new SlotId();
        $priantId = new PriantId();
        $inscription = new Inscription($editionId, $slotId, $priantId);
        $this->repository->add($inscription);

        self::assertSame($inscription, $this->repository->findById($inscription->getId()));
        self::assertNull($this->repository->findById(new InscriptionId()));

        $this->repository->remove($inscription);

        self::assertNull($this->repository->findById($inscription->getId()));
        self::assertSame(0, $this->repository->countByEdition($editionId));
        // RG-EP-1: the slot is free again, the priant may come back
        $this->repository->add(new Inscription($editionId, $slotId, $priantId));
        self::assertSame(1, $this->repository->countByEdition($editionId));
    }
}
