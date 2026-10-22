<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Domain\Time\ParisCalendar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for DoctrinePublicEditionViewFinder (F08-T01):
 * RG-ED-1 — the current public edition is the most recent scheduled one
 * whose opening date has been reached.
 */
final class DoctrinePublicEditionViewFinderTest extends KernelTestCase
{
    private PublicEditionViewFinder $finder;

    private EditionRepositoryInterface $repository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $testContainer = $container->get('test.service_container');
        $this->finder = $testContainer->get(PublicEditionViewFinder::class);
        $this->repository = $container->get(EditionRepositoryInterface::class);
        $this->em = $container->get('doctrine')->getManager();

        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM slot');
        $connection->executeStatement('DELETE FROM edition');
        $this->em->clear();
    }

    public function testCurrentReturnsNullWhenNoEditionExists(): void
    {
        self::assertNull($this->finder->current());
    }

    public function testCurrentReturnsNullWhenTheOnlyEditionIsADraft(): void
    {
        $this->createEdition(lifecycle: 'draft', opensAt: '2020-01-01 00:00:00', startsAt: '2099-01-01 00:00:00');

        self::assertNull($this->finder->current());
    }

    public function testCurrentReturnsNullWhenTheScheduledEditionHasNotOpenedYet(): void
    {
        // Opens far in the future
        $this->createEdition(lifecycle: 'scheduled', opensAt: '2099-06-01 00:00:00', startsAt: '2099-07-01 00:00:00');

        self::assertNull($this->finder->current());
    }

    public function testCurrentReturnsTheScheduledEditionOnceOpened(): void
    {
        $edition = $this->createEdition(lifecycle: 'scheduled', opensAt: '2020-01-01 00:00:00', startsAt: '2099-01-01 00:00:00');

        $view = $this->finder->current();

        self::assertNotNull($view);
        self::assertSame($edition->getId()->value->toRfc4122(), $view->id);
        self::assertSame('24 heures de prière', $view->title);
        self::assertSame('open', $view->status);
    }

    public function testCurrentReturnsTheMostRecentWhenTwoEditionsAreOpen(): void
    {
        $this->createEdition(lifecycle: 'scheduled', opensAt: '2020-01-01 00:00:00', startsAt: '2020-06-01 00:00:00', title: 'Édition ancienne');
        $newer = $this->createEdition(lifecycle: 'scheduled', opensAt: '2020-01-01 00:00:00', startsAt: '2099-01-01 00:00:00', title: 'Édition récente');

        $view = $this->finder->current();

        self::assertNotNull($view);
        self::assertSame($newer->getId()->value->toRfc4122(), $view->id);
        self::assertSame('Édition récente', $view->title);
    }

    public function testCurrentReturnsTheFinishedEditionWithComputedStatus(): void
    {
        // Started and finished long ago: still the most recent scheduled edition
        $this->createEdition(lifecycle: 'scheduled', opensAt: '2020-01-01 00:00:00', startsAt: '2020-01-02 00:00:00', title: 'Édition terminée');

        $view = $this->finder->current();

        self::assertNotNull($view);
        self::assertSame('finished', $view->status);
    }

    private function createEdition(
        string $lifecycle,
        string $opensAt,
        string $startsAt,
        string $title = '24 heures de prière',
    ): Edition {
        $edition = Edition::create(
            new EditionTitle($title),
            new Presentation(''),
            new \DateTimeImmutable($startsAt, new \DateTimeZone('UTC')),
            new \DateTimeImmutable($opensAt, new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        if ('scheduled' === $lifecycle) {
            $edition->schedule(new \DateTimeImmutable('2020-01-01 00:00:00', new \DateTimeZone('UTC')));
        }

        $this->repository->add($edition);
        $this->em->flush();

        return $edition;
    }
}
