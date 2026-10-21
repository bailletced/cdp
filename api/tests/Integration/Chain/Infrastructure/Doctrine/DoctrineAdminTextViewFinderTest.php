<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\AdminTextView;
use App\Chain\Application\Query\AdminTextViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Domain\Time\ParisCalendar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for DoctrineAdminTextViewFinder (F06-T07): accent-insensitive
 * search on title and source (Postgres unaccent), pagination and attribution
 * counts, read through raw SQL.
 */
final class DoctrineAdminTextViewFinderTest extends KernelTestCase
{
    private AdminTextViewFinder $finder;

    private TextRepositoryInterface $textRepository;

    private EditionRepositoryInterface $editionRepository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->finder = $container->get(AdminTextViewFinder::class);
        $this->textRepository = $container->get(TextRepositoryInterface::class);
        $this->editionRepository = $container->get(EditionRepositoryInterface::class);
        $this->em = $container->get('doctrine')->getManager();

        // Clean slate for each test
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM slot');
        $connection->executeStatement('DELETE FROM edition');
        $connection->executeStatement('DELETE FROM text');
        $this->em->clear();
    }

    public function testSearchFindsTextsRegardlessOfAccents(): void
    {
        $text = $this->createText('Oraison de Thérèse', 'Thérèse de Lisieux');
        $this->textRepository->add($text);
        $this->em->flush();

        $page = $this->finder->search('therese', 1, 20);

        self::assertSame(1, $page->total);
        self::assertCount(1, $page->items);
        self::assertSame($text->getId()->value->toRfc4122(), $page->items[0]->id);
        self::assertSame('Oraison de Thérèse', $page->items[0]->title);
    }

    public function testFindOneReturnsTheTextDetailsWithBodyHintsAndAttributionCount(): void
    {
        $text = $this->createText('Oraison de Thérèse', 'Thérèse de Lisieux', '<p>Corps <strong>riche</strong>.</p>', 'Méditer le matin.');
        $this->textRepository->add($text);
        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($text->getId()->value);
        $this->editionRepository->add($edition);
        $this->em->flush();

        $view = $this->finder->findOne($text->getId());

        self::assertNotNull($view);
        self::assertSame($text->getId()->value->toRfc4122(), $view->id);
        self::assertSame('Oraison de Thérèse', $view->title);
        self::assertSame('Thérèse de Lisieux', $view->source);
        self::assertSame('<p>Corps <strong>riche</strong>.</p>', $view->body);
        self::assertSame('Méditer le matin.', $view->hints);
        self::assertSame(1, $view->attributionCount);
    }

    public function testSearchAlsoMatchesTheSource(): void
    {
        $this->textRepository->add($this->createText('Psaume vingt-trois', 'La Bible'));
        $this->textRepository->add($this->createText('Oraison du soir', 'Jean de la Croix'));
        $this->em->flush();

        $page = $this->finder->search('croix', 1, 20);

        self::assertSame(1, $page->total);
        self::assertSame('Oraison du soir', $page->items[0]->title);
    }

    public function testSearchWithAnEmptyQueryReturnsAttributionCountsForEveryText(): void
    {
        $attributed = $this->createText('Texte attribué', 'Source une');
        $lonely = $this->createText('Texte isolé', 'Source deux');
        $this->textRepository->add($attributed);
        $this->textRepository->add($lonely);
        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($attributed->getId()->value);
        $edition->getSlots()[1]->setTextId($attributed->getId()->value);
        $this->editionRepository->add($edition);
        $this->em->flush();

        $page = $this->finder->search('', 1, 20);

        self::assertSame(2, $page->total);
        $counts = [];
        foreach ($page->items as $item) {
            $counts[$item->id] = $item->attributionCount;
        }
        self::assertSame(2, $counts[$attributed->getId()->value->toRfc4122()]);
        self::assertSame(0, $counts[$lonely->getId()->value->toRfc4122()]);
    }

    public function testSearchPaginatesResults(): void
    {
        $this->textRepository->add($this->createText('Psaume un', 'La Bible'));
        $this->textRepository->add($this->createText('Psaume deux', 'La Bible'));
        $this->textRepository->add($this->createText('Psaume trois', 'La Bible'));
        $this->em->flush();

        $firstPage = $this->finder->search('psaume', 1, 2);
        $secondPage = $this->finder->search('psaume', 2, 2);

        self::assertSame(3, $firstPage->total);
        self::assertSame(1, $firstPage->page);
        self::assertSame(2, $firstPage->itemsPerPage);
        self::assertSame(
            ['Psaume deux', 'Psaume trois'],
            array_map(static fn (AdminTextView $item): string => $item->title, $firstPage->items),
        );
        self::assertSame(2, $secondPage->page);
        self::assertSame(
            ['Psaume un'],
            array_map(static fn (AdminTextView $item): string => $item->title, $secondPage->items),
        );
    }

    public function testSearchEscapesLikeWildcardsInTheQuery(): void
    {
        $this->textRepository->add($this->createText('Psaume un', 'La Bible'));
        $this->textRepository->add($this->createText('Psaume deux', 'La Bible'));
        $this->em->flush();

        self::assertSame(0, $this->finder->search('%', 1, 20)->total);
        self::assertSame(0, $this->finder->search('_', 1, 20)->total);
        self::assertSame(2, $this->finder->search('psaume', 1, 20)->total);
    }

    public function testFindOneReturnsNullForUnknownId(): void
    {
        self::assertNull($this->finder->findOne(new TextId()));
    }

    private function createText(string $title, string $source, string $body = '<p>Corps du texte.</p>', ?string $hints = null): Text
    {
        return Text::create(
            new TextTitle($title),
            new TextSource($source),
            new RichText($body),
            $hints,
        );
    }

    private function createEdition(): Edition
    {
        return Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
    }
}
