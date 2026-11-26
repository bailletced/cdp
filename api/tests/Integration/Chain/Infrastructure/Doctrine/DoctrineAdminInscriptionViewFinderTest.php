<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Query\AdminInscriptionViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\SlotId;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for DoctrineAdminInscriptionViewFinder (F16-T03): raw
 * SQL reads must decode the Postgres uuid columns (returned as RFC 4122
 * strings) and expose the priant name and e-mail, which the public planning
 * never leaks (RG-PL-4).
 */
final class DoctrineAdminInscriptionViewFinderTest extends KernelTestCase
{
    private AdminInscriptionViewFinder $finder;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->finder = $container->get(AdminInscriptionViewFinder::class);
        $this->em = $container->get('doctrine')->getManager();

        // Clean slate for each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM inscription');
        $connection->executeStatement('DELETE FROM priant');
        foreach ($connection->fetchAllAssociative('SELECT id FROM edition') as $row) {
            $connection->executeStatement('DELETE FROM slot WHERE edition_id = ?', [$row['id']]);
            $connection->executeStatement('DELETE FROM edition WHERE id = ?', [$row['id']]);
        }
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    public function testItListsTheNamesAndEmailsOfTheInscriptionsOfAnEditionSlotBySlot(): void
    {
        [$edition, $inscriptions] = $this->createFixtures();

        $views = $this->finder->findByEdition($edition->getId());

        self::assertCount(3, $views);
        // Slot rank first (F16: the list is read slot by slot), then display name
        self::assertSame(1, $views[0]->slotRank);
        self::assertSame('Famille Martin', $views[0]->displayName);
        self::assertSame('famille.martin@bailletced.org', $views[0]->email);
        self::assertSame('self', $views[0]->origin);
        self::assertSame((string) $edition->getSlots()[0]->getId(), $views[0]->slotId);
        self::assertSame((string) $inscriptions[0]->getId(), $views[0]->inscriptionId);
        // An edition spans two days, so a slot label alone needs its day
        self::assertSame('samedi 28 novembre', $views[0]->dayLabel);
        self::assertSame('20h00–20h30', $views[0]->slotLabel);
        self::assertSame('2026-11-28T19:00:00+00:00', $views[0]->startsAtUtc);
        self::assertSame('2026-11-28T19:30:00+00:00', $views[0]->endsAtUtc);

        self::assertSame(2, $views[1]->slotRank);
        self::assertSame('Marie Dupont', $views[1]->displayName);
        self::assertSame('marie@bailletced.org', $views[1]->email);

        // A priant registered by an organizer may have no e-mail (F16)
        self::assertSame(2, $views[2]->slotRank);
        self::assertSame('Marie-grand-mère de Thomas', $views[2]->displayName);
        self::assertNull($views[2]->email);
        self::assertSame('organizer', $views[2]->origin);
    }

    public function testTheSlotFilterNarrowsTheListToOneSlot(): void
    {
        [$edition] = $this->createFixtures();
        $secondSlotId = new SlotId((string) $edition->getSlots()[1]->getId());

        $views = $this->finder->findByEdition($edition->getId(), $secondSlotId);

        self::assertCount(2, $views);
        self::assertSame(2, $views[0]->slotRank);
        self::assertSame(2, $views[1]->slotRank);
    }

    public function testAnEditionWithoutInscriptionsReturnsAnEmptyList(): void
    {
        $edition = $this->createEdition();
        $this->persistEdition($edition);

        self::assertSame([], $this->finder->findByEdition($edition->getId()));
    }

    /**
     * @return array{Edition, array{Inscription, Inscription, Inscription}}
     */
    private function createFixtures(): array
    {
        $edition = $this->createEdition();
        $this->persistEdition($edition);
        $slots = $edition->getSlots();

        /** @var PriantRepositoryInterface $priantRepository */
        $priantRepository = self::getContainer()->get(PriantRepositoryInterface::class);
        $martin = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $marie = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $grandMa = new Priant(null, new DisplayName('Marie-grand-mère de Thomas'));
        $priantRepository->add($martin);
        $priantRepository->add($marie);
        $priantRepository->add($grandMa);
        $this->em->flush();

        $inscriptions = [
            new Inscription($edition->getId(), $slots[0]->getId(), $martin->getId()),
            new Inscription($edition->getId(), $slots[1]->getId(), $marie->getId()),
            new Inscription($edition->getId(), $slots[1]->getId(), $grandMa->getId(), Inscription::ORIGIN_ORGANIZER),
        ];
        $repository = self::getContainer()->get(InscriptionRepositoryInterface::class);
        foreach ($inscriptions as $inscription) {
            $repository->add($inscription);
        }
        $this->em->flush();

        return [$edition, $inscriptions];
    }

    private function createEdition(): Edition
    {
        return Edition::create(
            new EditionTitle('24 heures de prière — novembre 2026'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-21 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
    }

    private function persistEdition(Edition $edition): void
    {
        /** @var EditionRepositoryInterface $editionRepository */
        $editionRepository = self::getContainer()->get(EditionRepositoryInterface::class);
        $editionRepository->add($edition);
        $this->em->flush();
    }
}
