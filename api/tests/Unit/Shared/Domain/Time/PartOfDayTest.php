<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Time;

use App\Shared\Domain\Time\PartOfDay;
use App\Shared\Domain\Time\TimeSlot;
use PHPUnit\Framework\TestCase;

final class PartOfDayTest extends TestCase
{
    public function testEveningFor18hTo0h(): void
    {
        // 2026-11-28 17:00 UTC = 18:00 CET (Paris)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-28 17:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-28 17:30:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Evening, PartOfDay::forSlot($slot));
    }

    public function testNightFor0hTo6h(): void
    {
        // 2026-11-28 23:00 UTC = 00:00 CET (Paris, midnight)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-28 23:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-28 23:30:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Night, PartOfDay::forSlot($slot));

        // 2026-11-29 04:00 UTC = 05:00 CET (Paris, early morning)
        $slot2 = new TimeSlot(
            new \DateTimeImmutable('2026-11-29 04:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-29 04:30:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Night, PartOfDay::forSlot($slot2));
    }

    public function testMorningFor6hTo12h(): void
    {
        // 2026-11-29 05:00 UTC = 06:00 CET (Paris)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-29 05:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-29 05:30:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Morning, PartOfDay::forSlot($slot));
    }

    public function testAfternoonFor12hTo18h(): void
    {
        // 2026-11-29 11:00 UTC = 12:00 CET (Paris)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-29 11:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-29 11:30:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Afternoon, PartOfDay::forSlot($slot));
    }

    public function testBoundaryAtMidnightIsNight(): void
    {
        // 2026-11-28 23:30 UTC = 00:30 CET (Paris, just after midnight)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-28 23:30:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-29 00:00:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Night, PartOfDay::forSlot($slot));
    }

    public function testBoundaryAt6hIsMorning(): void
    {
        // 2026-11-29 05:30 UTC = 06:30 CET (Paris)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-29 05:30:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-29 06:00:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Morning, PartOfDay::forSlot($slot));
    }

    public function testBoundaryAt18hIsEvening(): void
    {
        // 2026-11-28 16:30 UTC = 17:30 CET, but the slot starts at 17:00 UTC = 18:00 CET
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-28 17:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-28 17:30:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(PartOfDay::Evening, PartOfDay::forSlot($slot));
    }

    public function testFrenchLabel(): void
    {
        self::assertSame('soirée', PartOfDay::Evening->label());
        self::assertSame('nuit', PartOfDay::Night->label());
        self::assertSame('matin', PartOfDay::Morning->label());
        self::assertSame('après-midi', PartOfDay::Afternoon->label());
    }
}
