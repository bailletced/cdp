<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Repository;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Infrastructure\Doctrine\Repository\DoctrineInscriptionRepository;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineInscriptionRepositoryTest extends KernelTestCase
{
    private DoctrineInscriptionRepository $repository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $doctrine = $container->get('doctrine');

        $this->repository = new DoctrineInscriptionRepository($doctrine);
        $this->em = $doctrine->getManager();

        // Clean up test data before each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE inscription, priant, slot, edition CASCADE');
        $this->em->clear();
    }

    public function testAddAndCountByEdition(): void
    {
        $edition = $this->createEdition();
        $priant = $this->createPriant();

        $this->repository->add(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $priant->getId()));
        $this->repository->add(new Inscription($edition->getId(), $edition->getSlots()[1]->getId(), $priant->getId()));
        $this->em->flush();

        self::assertSame(2, $this->repository->countByEdition($edition->getId()));
    }

    public function testCountByEditionIsScopedToTheEdition(): void
    {
        $edition = $this->createEdition();
        $otherEdition = $this->createEdition();
        $priant = $this->createPriant();

        $this->repository->add(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $priant->getId()));
        $this->em->flush();

        self::assertSame(1, $this->repository->countByEdition($edition->getId()));
        self::assertSame(0, $this->repository->countByEdition($otherEdition->getId()));
    }

    public function testDuplicateSlotPriantIsRejectedByTheUniqueIndex(): void
    {
        $edition = $this->createEdition();
        $priant = $this->createPriant();
        $slot = $edition->getSlots()[0];

        $this->repository->add(new Inscription($edition->getId(), $slot->getId(), $priant->getId()));
        $this->em->flush();
        $this->em->clear();

        // RG-IN-3: one inscription per (slot, priant), enforced up to the database.
        $this->repository->add(new Inscription($edition->getId(), $slot->getId(), $priant->getId()));

        $this->expectException(UniqueConstraintViolationException::class);

        $this->em->flush();
    }

    public function testSamePriantOnDifferentSlotsIsAllowed(): void
    {
        $edition = $this->createEdition();
        $priant = $this->createPriant();

        $this->repository->add(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $priant->getId()));
        $this->repository->add(new Inscription($edition->getId(), $edition->getSlots()[1]->getId(), $priant->getId()));
        $this->em->flush();

        self::assertSame(2, $this->repository->countByEdition($edition->getId()));
    }

    public function testAddDoesNotFlush(): void
    {
        $edition = $this->createEdition();
        $priant = $this->createPriant();

        $this->repository->add(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $priant->getId()));

        // The repository only registers the aggregate: nothing reaches the database on its own.
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM inscription'));

        $this->em->flush();

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT count(*) FROM inscription'));
    }

    private function createEdition(): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }

    private function createPriant(): Priant
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));

        $this->em->persist($priant);
        $this->em->flush();

        return $priant;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE inscription, priant, slot, edition CASCADE');
        $this->em->clear();
    }
}
