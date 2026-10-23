<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Repository;

use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test case for PriantRepository implementations.
 * Concrete implementations should extend this class and implement getRepository().
 */
abstract class PriantRepositoryTestCase extends TestCase
{
    protected PriantRepositoryInterface $repository;

    /**
     * Get the repository instance to test.
     */
    abstract protected function getRepository(): PriantRepositoryInterface;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository();
    }

    public function testAddAndFindById(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $this->repository->add($priant);

        $found = $this->repository->findById($priant->getId());
        $this->assertNotNull($found);
        $this->assertSame($priant, $found);
    }

    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $found = $this->repository->findById(new PriantId());
        $this->assertNull($found);
    }

    public function testAddAndFindByEmail(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $this->repository->add($priant);

        $found = $this->repository->findByEmail(new Email('marie@bailletced.org'));
        $this->assertNotNull($found);
        $this->assertSame($priant, $found);
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $this->repository->add($priant);

        $found = $this->repository->findByEmail(new Email('MARIE@BAILLETCED.ORG'));
        $this->assertNotNull($found);
        $this->assertSame($priant, $found);
    }

    public function testFindByEmailReturnsNullForNonExistentEmail(): void
    {
        $found = $this->repository->findByEmail(new Email('inconnu@bailletced.org'));
        $this->assertNull($found);
    }

    public function testSeveralPriantsWithoutEmailAreAllowed(): void
    {
        $priant1 = new Priant(null, new DisplayName('Famille Un'));
        $priant2 = new Priant(null, new DisplayName('Famille Deux'));

        $this->repository->add($priant1);
        $this->repository->add($priant2);

        $this->assertSame($priant1, $this->repository->findById($priant1->getId()));
        $this->assertSame($priant2, $this->repository->findById($priant2->getId()));
    }
}
