<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\PlanningView;
use App\Chain\Application\Query\PlanningViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\Repository\PriantRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for DoctrinePlanningViewFinder (F08-T03, F09-T13):
 * the planning returns 48 slots in a single query, with text title/source,
 * temporal state and registration count (RG-PL-1), and no priant data
 * (RG-PL-4).
 */
final class DoctrinePlanningViewFinderTest extends KernelTestCase
{
    private PlanningViewFinder $finder;

    private EditionRepositoryInterface $editionRepository;

    private TextRepositoryInterface $textRepository;

    private PriantRepositoryInterface $priantRepository;

    private InscriptionRepositoryInterface $inscriptionRepository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $testContainer = $container->get('test.service_container');
        $this->finder = $testContainer->get(PlanningViewFinder::class);
        $this->editionRepository = $container->get(EditionRepositoryInterface::class);
        $this->textRepository = $container->get(TextRepositoryInterface::class);
        $this->priantRepository = $container->get(PriantRepositoryInterface::class);
        $this->inscriptionRepository = $container->get(InscriptionRepositoryInterface::class);
        $this->em = $container->get('doctrine')->getManager();

        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM inscription');
        $connection->executeStatement('DELETE FROM priant');
        $connection->executeStatement('DELETE FROM slot');
        $connection->executeStatement('DELETE FROM edition');
        $connection->executeStatement('DELETE FROM text');
        $this->em->clear();
    }

    public function testFindByEditionReturnsNullForAnUnknownEdition(): void
    {
        self::assertNull($this->finder->findByEdition(new EditionId()));
    }

    public function testFindByEditionReturnsNullForADraftEdition(): void
    {
        $edition = $this->createEdition(lifecycle: 'draft');

        self::assertNull($this->finder->findByEdition($edition->getId()));
    }

    public function testFindByEditionReturns48SlotsForAPublicEdition(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');

        $view = $this->finder->findByEdition($edition->getId());

        self::assertNotNull($view);
        self::assertSame($edition->getId()->value->toRfc4122(), $view->editionId);
        self::assertCount(48, $view->slots);
    }

    public function testSlotsAreInChronologicalOrderWithRank(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');

        $view = $this->finder->findByEdition($edition->getId());
        self::assertNotNull($view);

        foreach ($view->slots as $index => $slot) {
            self::assertSame($index + 1, $slot->rank);
            self::assertNotEmpty($slot->id);
            self::assertNotEmpty($slot->label);
            self::assertContains($slot->partOfDay, ['evening', 'night', 'morning', 'afternoon']);
            self::assertContains($slot->state, ['past', 'current', 'future']);
        }
    }

    public function testSlotsIncludeTextTitleAndSourceWhenAttributed(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');
        $text = Text::create(
            new TextTitle('Oraison de Thérèse'),
            new TextSource('Thérèse de Lisieux'),
            new RichText('<p>Corps.</p>'),
        );
        $this->textRepository->add($text);
        $edition->getSlots()[0]->setTextId($text->getId()->value);
        $this->em->flush();

        $view = $this->finder->findByEdition($edition->getId());
        self::assertNotNull($view);

        self::assertSame('Oraison de Thérèse', $view->slots[0]->textTitle);
        self::assertSame('Thérèse de Lisieux', $view->slots[0]->textSource);
        self::assertNull($view->slots[1]->textTitle);
        self::assertNull($view->slots[1]->textSource);
    }

    public function testEachSlotListsItsParticipantsInRegistrationOrder(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');
        $marie = $this->createPriant('marie@bailletced.org', 'Marie Dupont');
        $jean = $this->createPriant('jean@bailletced.org', 'Jean Martin');
        $slot = $edition->getSlots()[2];
        $first = new Inscription($edition->getId(), $slot->getId(), $marie->getId());
        $second = new Inscription($edition->getId(), $slot->getId(), $jean->getId());
        $this->inscriptionRepository->add($first);
        $this->inscriptionRepository->add($second);
        $this->em->flush();

        $view = $this->finder->findByEdition($edition->getId());
        self::assertNotNull($view);

        // The finder reads everything; the provider masks it (F10-T08, RG-PL-4)
        $participants = $view->slots[2]->participants;
        self::assertCount(2, $participants);
        self::assertSame((string) $first->getId(), $participants[0]->inscriptionId);
        self::assertSame((string) $marie->getId(), $participants[0]->priantId);
        self::assertSame('Marie Dupont', $participants[0]->displayName);
        // RG-LV-2: no pointage before F19
        self::assertFalse($participants[0]->prayed);
        self::assertSame('Jean Martin', $participants[1]->displayName);
        self::assertSame([], $view->slots[3]->participants);
    }

    public function testTheGridIsReadInASingleQuery(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');
        $marie = $this->createPriant('marie@bailletced.org', 'Marie Dupont');
        $this->inscriptionRepository->add(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $marie->getId()));
        $this->em->flush();
        $queries = $this->countQueries(fn (): ?PlanningView => $this->finder->findByEdition($edition->getId()));

        // One query to check the edition is public, one for the 48 slots
        self::assertSame(2, $queries);
    }

    public function testEachInscriptionCountsForOneRegistration(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');
        $priant = $this->createPriant('marie@bailletced.org', 'Marie Dupont');
        $this->inscriptionRepository->add(new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $priant->getId()));
        $this->em->flush();

        $view = $this->finder->findByEdition($edition->getId());
        self::assertNotNull($view);

        // RG-PL-1: one inscription counts for exactly one registration.
        self::assertSame(1, $view->slots[0]->registrationCount);
        self::assertTrue($view->slots[0]->occupied);
        self::assertSame(0, $view->slots[1]->registrationCount);
        self::assertFalse($view->slots[1]->occupied);
    }

    public function testTwoPriantsOnTheSameSlotCountAsTwoRegistrations(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');
        $marie = $this->createPriant('marie@bailletced.org', 'Marie Dupont');
        $jean = $this->createPriant('jean@bailletced.org', 'Jean Martin');
        $slot = $edition->getSlots()[2];
        $this->inscriptionRepository->add(new Inscription($edition->getId(), $slot->getId(), $marie->getId()));
        $this->inscriptionRepository->add(new Inscription($edition->getId(), $slot->getId(), $jean->getId()));
        $this->em->flush();

        $view = $this->finder->findByEdition($edition->getId());
        self::assertNotNull($view);

        self::assertSame(2, $view->slots[2]->registrationCount);
        self::assertTrue($view->slots[2]->occupied);
        self::assertSame(0, $view->slots[3]->registrationCount);
        self::assertFalse($view->slots[3]->occupied);
    }

    private function countQueries(callable $read): int
    {
        $debugData = self::getContainer()->get('doctrine.debug_data_holder');
        \assert($debugData instanceof DebugDataHolder);
        $debugData->reset();

        $read();

        return \count($debugData->getData()['default'] ?? []);
    }

    private function createPriant(string $email, string $displayName): Priant
    {
        $priant = new Priant(new Email($email), new DisplayName($displayName));
        $this->priantRepository->add($priant);
        $this->em->flush();

        return $priant;
    }

    private function createEdition(string $lifecycle): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2099-01-01 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2020-01-01 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        if ('scheduled' === $lifecycle) {
            $edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        }

        $this->editionRepository->add($edition);
        $this->em->flush();

        return $edition;
    }
}
