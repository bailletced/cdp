<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\InMemory\Query;

use App\Chain\Application\Dto\AdminTextView;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminTextViewFinder;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class InMemoryAdminTextViewFinderTest extends TestCase
{
    public function testSearchFindsTextsRegardlessOfAccents(): void
    {
        $textRepository = new InMemoryTextRepository();
        $finder = new InMemoryAdminTextViewFinder($textRepository, new InMemoryEditionRepository());

        $textRepository->add(Text::create(
            new TextTitle('Oraison de Thérèse'),
            new TextSource('Thérèse de Lisieux'),
            new RichText('<p>Corps du texte.</p>'),
        ));

        $page = $finder->search('therese', 1, 20);

        self::assertSame(1, $page->total);
        self::assertCount(1, $page->items);
        self::assertSame('Oraison de Thérèse', $page->items[0]->title);
    }

    public function testSearchReturnsAttributionCounts(): void
    {
        $textRepository = new InMemoryTextRepository();
        $editionRepository = new InMemoryEditionRepository();
        $finder = new InMemoryAdminTextViewFinder($textRepository, $editionRepository);

        $attributed = Text::create(new TextTitle('Texte attribué'), new TextSource('Source une'), new RichText('<p>Un.</p>'));
        $lonely = Text::create(new TextTitle('Texte isolé'), new TextSource('Source deux'), new RichText('<p>Deux.</p>'));
        $textRepository->add($attributed);
        $textRepository->add($lonely);

        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($attributed->getId()->value);
        $edition->getSlots()[1]->setTextId($attributed->getId()->value);
        $editionRepository->add($edition);
        $otherEdition = $this->createEdition();
        $otherEdition->getSlots()[0]->setTextId($attributed->getId()->value);
        $editionRepository->add($otherEdition);

        $page = $finder->search('', 1, 20);

        self::assertSame(2, $page->total);
        $counts = [];
        foreach ($page->items as $item) {
            $counts[$item->id] = $item->attributionCount;
        }
        self::assertSame(3, $counts[$attributed->getId()->value->toRfc4122()]);
        self::assertSame(0, $counts[$lonely->getId()->value->toRfc4122()]);
    }

    public function testSearchPaginatesResults(): void
    {
        $textRepository = new InMemoryTextRepository();
        $finder = new InMemoryAdminTextViewFinder($textRepository, new InMemoryEditionRepository());

        $textRepository->add(Text::create(new TextTitle('Psaume un'), new TextSource('La Bible'), new RichText('<p>Un.</p>')));
        $textRepository->add(Text::create(new TextTitle('Psaume deux'), new TextSource('La Bible'), new RichText('<p>Deux.</p>')));
        $textRepository->add(Text::create(new TextTitle('Psaume trois'), new TextSource('La Bible'), new RichText('<p>Trois.</p>')));

        $firstPage = $finder->search('psaume', 1, 2);
        $secondPage = $finder->search('psaume', 2, 2);

        self::assertSame(3, $firstPage->total);
        self::assertSame(
            ['Psaume deux', 'Psaume trois'],
            array_map(static fn (AdminTextView $item): string => $item->title, $firstPage->items),
        );
        self::assertSame(
            ['Psaume un'],
            array_map(static fn (AdminTextView $item): string => $item->title, $secondPage->items),
        );
    }

    public function testFindOneReturnsTheTextDetailsWithBodyHintsAndAttributionCount(): void
    {
        $textRepository = new InMemoryTextRepository();
        $editionRepository = new InMemoryEditionRepository();
        $finder = new InMemoryAdminTextViewFinder($textRepository, $editionRepository);

        $text = Text::create(
            new TextTitle('Oraison de Thérèse'),
            new TextSource('Thérèse de Lisieux'),
            new RichText('<p>Corps <strong>riche</strong>.</p>'),
            'Méditer le matin.',
        );
        $textRepository->add($text);
        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($text->getId()->value);
        $editionRepository->add($edition);

        $view = $finder->findOne($text->getId());

        self::assertNotNull($view);
        self::assertSame($text->getId()->value->toRfc4122(), $view->id);
        self::assertSame('Oraison de Thérèse', $view->title);
        self::assertSame('Thérèse de Lisieux', $view->source);
        self::assertSame('<p>Corps <strong>riche</strong>.</p>', $view->body);
        self::assertSame('Méditer le matin.', $view->hints);
        self::assertSame(1, $view->attributionCount);
    }

    public function testFindOneReturnsNullForUnknownId(): void
    {
        $finder = new InMemoryAdminTextViewFinder(new InMemoryTextRepository(), new InMemoryEditionRepository());

        self::assertNull($finder->findOne(new TextId()));
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
