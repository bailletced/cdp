<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Time;

use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class ParisCalendarFromParisLocalTest extends TestCase
{
    private ParisCalendar $calendar;

    protected function setUp(): void
    {
        $this->calendar = new ParisCalendar();
    }

    public function testFromParisLocalNormalWinterTime(): void
    {
        // 2026-11-28 20:00 CET (Paris, winter time UTC+1) = 2026-11-28 19:00 UTC
        $parisLocal = new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris'));

        $utc = $this->calendar->fromParisLocal($parisLocal);

        self::assertSame('2026-11-28 19:00:00', $utc->format('Y-m-d H:i:s'));
        self::assertSame('UTC', $utc->getTimezone()->getName());
    }

    public function testFromParisLocalNormalSummerTime(): void
    {
        // 2026-07-15 20:00 CEST (Paris, summer time UTC+2) = 2026-07-15 18:00 UTC
        $parisLocal = new \DateTimeImmutable('2026-07-15 20:00:00', new \DateTimeZone('Europe/Paris'));

        $utc = $this->calendar->fromParisLocal($parisLocal);

        self::assertSame('2026-07-15 18:00:00', $utc->format('Y-m-d H:i:s'));
    }

    public function testFromParisLocalHalfHour(): void
    {
        // 2026-11-28 20:30 CET = 2026-11-28 19:30 UTC
        $parisLocal = new \DateTimeImmutable('2026-11-28 20:30:00', new \DateTimeZone('Europe/Paris'));

        $utc = $this->calendar->fromParisLocal($parisLocal);

        self::assertSame('2026-11-28 19:30:00', $utc->format('Y-m-d H:i:s'));
    }

    public function testFromParisLocalRejectsInvalidMinutes(): void
    {
        $parisLocal = new \DateTimeImmutable('2026-11-28 20:15:00', new \DateTimeZone('Europe/Paris'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(':00 or :30');

        $this->calendar->fromParisLocal($parisLocal);
    }

    public function testFromParisLocalAmbiguousTimeTakesFirstOccurrence(): void
    {
        // 25/10/2026: fall-back at 03:00 CEST → 02:00 CET.
        // 02:30 Paris local is ambiguous: it occurs at 00:30 UTC (CEST, summer) and 01:30 UTC (CET, winter).
        // RG-ED-7: ambiguous time takes the first occurrence (summer time, earliest UTC instant).
        $parisLocal = new \DateTimeImmutable('2026-10-25 02:30:00', new \DateTimeZone('Europe/Paris'));

        $utc = $this->calendar->fromParisLocal($parisLocal);

        self::assertSame('2026-10-25 00:30:00', $utc->format('Y-m-d H:i:s'));
    }

    public function testFromParisLocalNonExistentTimeShiftsForward(): void
    {
        // 28/03/2027: spring-forward at 02:00 CET → 03:00 CEST.
        // 02:30 Paris local does not exist (clocks jump from 02:00 to 03:00).
        // RG-ED-7: non-existent time is shifted forward by one hour → 03:30 CEST = 01:30 UTC.
        $parisLocal = new \DateTimeImmutable('2027-03-28 02:30:00', new \DateTimeZone('Europe/Paris'));

        $utc = $this->calendar->fromParisLocal($parisLocal);

        // PHP resolves non-existent times by shifting forward: 02:30 CET → 03:30 CEST = 01:30 UTC
        self::assertSame('2027-03-28 01:30:00', $utc->format('Y-m-d H:i:s'));
    }

    public function testEveAt18h(): void
    {
        // Edition starts 2026-11-28 19:00 UTC = 2026-11-28 20:00 CET (Paris).
        // Eve at 18:00 Paris = 2026-11-27 18:00 CET = 2026-11-27 17:00 UTC.
        $startsAtUtc = new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC'));

        $eve = $this->calendar->eveAt18h($startsAtUtc);

        self::assertSame('2026-11-27 17:00:00', $eve->format('Y-m-d H:i:s'));
        self::assertSame('UTC', $eve->getTimezone()->getName());
    }

    public function testEveAt18hAcrossDstChange(): void
    {
        // Edition starts 2026-10-25 22:00 UTC. DST fall-back is at 01:00 UTC on Oct 25,
        // so at 22:00 UTC it is already winter time (CET, UTC+1) → 23:00 Paris on Oct 25.
        // Eve at 18:00 Paris = Oct 24 18:00 CEST (still summer time) = 2026-10-24 16:00 UTC.
        $startsAtUtc = new \DateTimeImmutable('2026-10-25 22:00:00', new \DateTimeZone('UTC'));

        $eve = $this->calendar->eveAt18h($startsAtUtc);

        self::assertSame('2026-10-24 16:00:00', $eve->format('Y-m-d H:i:s'));
    }
}
