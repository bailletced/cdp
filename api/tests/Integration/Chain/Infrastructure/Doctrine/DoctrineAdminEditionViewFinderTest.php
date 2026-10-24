<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Query\AdminEditionViewFinder;
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
 * Integration test for DoctrineAdminEditionViewFinder (F05-T22): raw SQL
 * reads must decode the Postgres uuid columns (returned as RFC 4122 strings
 * by the driver, not as 16-byte binary).
 */
final class DoctrineAdminEditionViewFinderTest extends KernelTestCase
{
    private AdminEditionViewFinder $finder;

    private EditionRepositoryInterface $repository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->finder = $container->get(AdminEditionViewFinder::class);
        $this->repository = $container->get(EditionRepositoryInterface::class);
        $this->em = $container->get('doctrine')->getManager();

        // Clean slate for each test
        $connection = $this->em->getConnection();
        foreach ($connection->fetchAllAssociative('SELECT id FROM edition') as $row) {
            $connection->executeStatement('DELETE FROM slot WHERE edition_id = ?', [$row['id']]);
            $connection->executeStatement('DELETE FROM edition WHERE id = ?', [$row['id']]);
        }
        $connection->executeStatement('DELETE FROM text');
        $this->em->clear();
    }

    public function testFindAllReturnsThePersistedEditionWithItsSlots(): void
    {
        $edition = $this->createEdition();
        $this->repository->add($edition);
        $this->em->flush();

        $views = $this->finder->findAll();

        self::assertCount(1, $views);
        $view = $views[0];
        self::assertSame($edition->getId()->value->toRfc4122(), $view->id);
        self::assertSame('24 heures de prière — novembre 2026', $view->title);
        self::assertSame('Une chaîne de prière continue.', $view->presentation);
        self::assertSame('2026-11-28T19:00:00+00:00', $view->startsAtUtc);
        self::assertSame('2026-11-21T19:00:00+00:00', $view->opensAtUtc);
        self::assertSame('draft', $view->lifecycle);
        self::assertSame('draft', $view->status);
        self::assertSame(1, $view->version);
        self::assertCount(48, $view->slots);
    }

    public function testFindOneReturnsThePersistedEdition(): void
    {
        $edition = $this->createEdition();
        $this->repository->add($edition);
        $this->em->flush();

        $view = $this->finder->findOne($edition->getId());

        self::assertNotNull($view);
        self::assertSame($edition->getId()->value->toRfc4122(), $view->id);
        self::assertCount(48, $view->slots);
    }

    public function testItDetailsTheTextOfEachSlotTheDuplicatesAndTheSlotsWithoutText(): void
    {
        $texts = self::getContainer()->get(TextRepositoryInterface::class);
        $therese = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Dieu seul suffit.</p>'));
        $psalm = Text::create(new TextTitle('L’Éternel est mon berger'), new TextSource('Psaume 23'), new RichText('<p>Je ne manquerai de rien.</p>'));
        $texts->add($therese);
        $texts->add($psalm);

        // Slots 1 and 2 share a text (US-07.1), slot 3 has its own, 45 have none
        $edition = $this->createEdition();
        $edition->distributeTexts([$therese->getId(), $therese->getId(), $psalm->getId()], 1);
        $this->repository->add($edition);
        $this->em->flush();

        $view = $this->finder->findOne($edition->getId());

        self::assertNotNull($view);
        self::assertSame($edition->getSlots()[0]->getId()->value->toRfc4122(), $view->slots[0]->id);
        self::assertSame('Que rien ne te trouble', $view->slots[0]->textTitle);
        self::assertSame('Thérèse d’Avila', $view->slots[0]->textSource);
        self::assertTrue($view->slots[0]->textAlreadyUsed);
        self::assertTrue($view->slots[1]->textAlreadyUsed);
        self::assertSame('L’Éternel est mon berger', $view->slots[2]->textTitle);
        self::assertFalse($view->slots[2]->textAlreadyUsed);
        self::assertNull($view->slots[3]->textTitle);
        self::assertNull($view->slots[3]->textSource);
        self::assertFalse($view->slots[3]->textAlreadyUsed);

        // RG-ED-4 and the « texte déjà utilisé » badge rely on these totals
        self::assertSame(45, $view->slotsWithoutText);
        self::assertSame([$therese->getId()->value->toRfc4122()], $view->duplicatedTextIds);
    }

    public function testFindOneReturnsNullForUnknownId(): void
    {
        self::assertNull($this->finder->findOne(new EditionId()));
    }

    private function createEdition(): Edition
    {
        return Edition::create(
            new EditionTitle('24 heures de prière — novembre 2026'),
            new Presentation('Une chaîne de prière continue.'),
            new \DateTimeImmutable('2026-11-28T19:00:00+00:00'),
            new \DateTimeImmutable('2026-11-21T19:00:00+00:00'),
            new ParisCalendar(),
        );
    }
}
