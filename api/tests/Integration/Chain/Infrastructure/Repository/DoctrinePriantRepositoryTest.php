<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Repository;

use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrinePriantRepository;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrinePriantRepositoryTest extends KernelTestCase
{
    private DoctrinePriantRepository $repository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $doctrine = $container->get('doctrine');

        $this->repository = new DoctrinePriantRepository($doctrine);
        $this->em = $doctrine->getManager();

        // Clean up test data before each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE priant CASCADE');
        $this->em->clear();
    }

    public function testAddAndFindById(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $this->repository->add($priant);
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findById($priant->getId());
        $this->assertNotNull($found);
        $this->assertEquals($priant->getId(), $found->getId());
        $this->assertSame('Marie Dupont', $found->getDisplayName()->value);
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
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findByEmail(new Email('marie@bailletced.org'));
        $this->assertNotNull($found);
        $this->assertEquals($priant->getId(), $found->getId());
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $this->repository->add($priant);
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findByEmail(new Email('MARIE@BAILLETCED.ORG'));
        $this->assertNotNull($found);
        $this->assertEquals($priant->getId(), $found->getId());
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
        $this->em->flush();
        $this->em->clear();

        $found1 = $this->repository->findById($priant1->getId());
        $found2 = $this->repository->findById($priant2->getId());
        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertNull($found1->getEmail());
        $this->assertNull($found2->getEmail());
    }

    public function testEmailUniquenessIsEnforcedByThePartialUniqueIndex(): void
    {
        $this->repository->add(new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont')));
        $this->em->flush();
        $this->em->clear();

        // Same email, different case: the lower(email) unique index rejects it (RG-IN-2).
        $this->repository->add(new Priant(new Email('MARIE@BAILLETCED.ORG'), new DisplayName('Autre Marie')));

        $this->expectException(UniqueConstraintViolationException::class);

        $this->em->flush();
    }

    public function testAddDoesNotFlush(): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $this->repository->add($priant);

        // The repository only registers the aggregate: nothing reaches the database on its own.
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM priant'));

        $this->em->flush();

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM priant'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE priant CASCADE');
        $this->em->clear();
    }
}
