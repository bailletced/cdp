<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\InMemory\Query;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Application\ScheduleEditionHandler;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminEditionViewFinder;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
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

        $finder = new InMemoryAdminEditionViewFinder($repository, $clock);

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

        $finder = new InMemoryAdminEditionViewFinder($repository, $clock);

        self::assertNull($finder->findOne(new \App\Chain\Domain\ValueObject\EditionId()));
    }
}
