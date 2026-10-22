<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Query\PlanningViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Domain\Time\ParisCalendar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for DoctrinePlanningViewFinder (F08-T03):
 * the planning returns 48 slots in a single query, with text title/source
 * and temporal state, and no priant data.
 */
final class DoctrinePlanningViewFinderTest extends KernelTestCase
{
    private PlanningViewFinder $finder;

    private EditionRepositoryInterface $editionRepository;

    private TextRepositoryInterface $textRepository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $testContainer = $container->get('test.service_container');
        $this->finder = $testContainer->get(PlanningViewFinder::class);
        $this->editionRepository = $container->get(EditionRepositoryInterface::class);
        $this->textRepository = $container->get(TextRepositoryInterface::class);
        $this->em = $container->get('doctrine')->getManager();

        $connection = $this->em->getConnection();
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

    public function testNoPriantDataIsExposed(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled');

        $view = $this->finder->findByEdition($edition->getId());
        self::assertNotNull($view);

        // The view must not contain any priant-related fields (RG-PL-4)
        foreach ($view->slots as $slot) {
            $data = (array) $slot;
            self::assertArrayNotHasKey('registrationCount', $data);
            self::assertArrayNotHasKey('isMine', $data);
            self::assertArrayNotHasKey('priantName', $data);
        }
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
