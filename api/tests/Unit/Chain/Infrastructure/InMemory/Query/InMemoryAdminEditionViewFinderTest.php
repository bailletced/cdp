<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\InMemory\Query;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Application\ScheduleEditionHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminEditionViewFinder;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class InMemoryAdminEditionViewFinderTest extends TestCase
{
    public function testFindAllReturnsEditionsWithComputedStatus(): void
    {
        $repository = new InMemoryEditionRepository();
        $calendar = new ParisCalendar();
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-20 12:00:00', new \DateTimeZone('UTC'));
            }
        };

        $finder = new InMemoryAdminEditionViewFinder($repository, new InMemoryTextRepository(), $clock);

        $createHandler = new CreateEditionHandler($repository, $calendar);
        $createResult = ($createHandler)(new CreateEdition(
            new EditionTitle('Édition brouillon'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));

        $scheduleHandler = new ScheduleEditionHandler($repository, $clock);
        ($scheduleHandler)(new ScheduleEdition($createResult->editionId));

        $all = $finder->findAll();

        self::assertCount(1, $all);
        self::assertSame('scheduled', $all[0]->lifecycle);
        self::assertSame('open', $all[0]->status); // now=2026-11-20, opensAt=2026-11-13, startsAt=2026-11-28
        self::assertCount(48, $all[0]->slots);
        self::assertSame(1, $all[0]->slots[0]->rank);
    }

    public function testFindOneReturnsNullForUnknownId(): void
    {
        $repository = new InMemoryEditionRepository();
        $calendar = new ParisCalendar();
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-20 12:00:00', new \DateTimeZone('UTC'));
            }
        };

        $finder = new InMemoryAdminEditionViewFinder($repository, new InMemoryTextRepository(), $clock);

        self::assertNull($finder->findOne(new \App\Chain\Domain\ValueObject\EditionId()));
    }

    public function testItDetailsTheTextOfEachSlotTheDuplicatesAndTheSlotsWithoutText(): void
    {
        $editions = new InMemoryEditionRepository();
        $texts = new InMemoryTextRepository();
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-20 12:00:00', new \DateTimeZone('UTC'));
            }
        };
        $therese = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Dieu seul suffit.</p>'));
        $psalm = Text::create(new TextTitle('L’Éternel est mon berger'), new TextSource('Psaume 23'), new RichText('<p>Je ne manquerai de rien.</p>'));
        $texts->add($therese);
        $texts->add($psalm);

        $edition = Edition::create(
            new EditionTitle('Édition'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28T19:00:00+00:00'),
            new \DateTimeImmutable('2026-11-21T19:00:00+00:00'),
            new ParisCalendar(),
        );
        $edition->distributeTexts([$therese->getId(), $therese->getId(), $psalm->getId()], 1);
        $editions->add($edition);

        $view = new InMemoryAdminEditionViewFinder($editions, $texts, $clock)->findOne($edition->getId());

        self::assertNotNull($view);
        self::assertSame((string) $edition->getSlots()[0]->getId(), $view->slots[0]->id);
        self::assertSame('Que rien ne te trouble', $view->slots[0]->textTitle);
        self::assertSame('Thérèse d’Avila', $view->slots[0]->textSource);
        self::assertTrue($view->slots[0]->textAlreadyUsed);
        self::assertTrue($view->slots[1]->textAlreadyUsed);
        self::assertSame('L’Éternel est mon berger', $view->slots[2]->textTitle);
        self::assertFalse($view->slots[2]->textAlreadyUsed);
        self::assertNull($view->slots[3]->textTitle);
        self::assertFalse($view->slots[3]->textAlreadyUsed);
        self::assertSame(45, $view->slotsWithoutText);
        self::assertSame([(string) $therese->getId()], $view->duplicatedTextIds);
    }
}
