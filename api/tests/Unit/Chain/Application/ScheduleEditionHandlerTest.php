<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Application\ScheduleEditionHandler;
use App\Chain\Application\UnscheduleEdition;
use App\Chain\Application\UnscheduleEditionHandler;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Infrastructure\Repository\InMemoryEditionRepository;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class ScheduleEditionHandlerTest extends TestCase
{
    public function testItSchedulesAnEdition(): void
    {
        $repository = new InMemoryEditionRepository();
        $calendar = new ParisCalendar();
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC'));
            }
        };

        $createHandler = new CreateEditionHandler($repository, $calendar);
        $createResult = ($createHandler)(new CreateEdition(
            new EditionTitle('24 heures'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));

        $scheduleHandler = new ScheduleEditionHandler($repository, $clock);
        ($scheduleHandler)(new ScheduleEdition($createResult->editionId));

        $edition = $repository->findById($createResult->editionId);
        self::assertNotNull($edition);
        self::assertSame('scheduled', $edition->getLifecycle());
        self::assertNotNull($edition->getScheduledAt());
    }

    public function testItUnschedulesAnEdition(): void
    {
        $repository = new InMemoryEditionRepository();
        $calendar = new ParisCalendar();
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC'));
            }
        };

        $createHandler = new CreateEditionHandler($repository, $calendar);
        $createResult = ($createHandler)(new CreateEdition(
            new EditionTitle('24 heures'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));

        $scheduleHandler = new ScheduleEditionHandler($repository, $clock);
        ($scheduleHandler)(new ScheduleEdition($createResult->editionId));

        $unscheduleHandler = new UnscheduleEditionHandler($repository, $clock);
        ($unscheduleHandler)(new UnscheduleEdition($createResult->editionId));

        $edition = $repository->findById($createResult->editionId);
        self::assertNotNull($edition);
        self::assertSame('draft', $edition->getLifecycle());
        self::assertNull($edition->getScheduledAt());
    }

    public function testItThrowsWhenSchedulingUnknownEdition(): void
    {
        $repository = new InMemoryEditionRepository();
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC'));
            }
        };

        $scheduleHandler = new ScheduleEditionHandler($repository, $clock);

        $this->expectException(EditionNotFoundException::class);

        ($scheduleHandler)(new ScheduleEdition(new EditionId()));
    }

    public function testItThrowsWhenUnschedulingUnknownEdition(): void
    {
        $repository = new InMemoryEditionRepository();
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC'));
            }
        };

        $unscheduleHandler = new UnscheduleEditionHandler($repository, $clock);

        $this->expectException(EditionNotFoundException::class);

        ($unscheduleHandler)(new UnscheduleEdition(new EditionId()));
    }
}
