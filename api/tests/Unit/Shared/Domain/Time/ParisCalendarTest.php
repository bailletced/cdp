<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Time;

use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\Time\TimeSlot;
use PHPUnit\Framework\TestCase;

final class ParisCalendarTest extends TestCase
{
    private ParisCalendar $calendar;

    protected function setUp(): void
    {
        $this->calendar = new ParisCalendar();
    }

    public function testSliceProduces48ContiguousSlotsOf30Minutes(): void
    {
        $startsAt = new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC'));

        $slots = $this->calendar->slice($startsAt);

        self::assertCount(48, $slots);
        self::assertContainsOnlyInstancesOf(TimeSlot::class, $slots);

        // First slot starts at startsAt, each slot is exactly 30 minutes
        self::assertSame('2026-11-28 19:00:00', $slots[0]->startsAt->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-28 19:30:00', $slots[0]->endsAt->format('Y-m-d H:i:s'));

        // Slots are contiguous: each slot's end equals the next slot's start
        for ($i = 0; $i < 47; ++$i) {
            self::assertSame(
                $slots[$i]->endsAt->format('Y-m-d H:i:s'),
                $slots[$i + 1]->startsAt->format('Y-m-d H:i:s'),
                \sprintf('Slot %d end must equal slot %d start', $i, $i + 1),
            );
        }

        // Last slot ends 24 hours after the start
        self::assertSame('2026-11-29 19:00:00', $slots[47]->endsAt->format('Y-m-d H:i:s'));
    }

    public function testSliceAcceptsHalfHourStart(): void
    {
        $startsAt = new \DateTimeImmutable('2026-11-28 19:30:00', new \DateTimeZone('UTC'));

        $slots = $this->calendar->slice($startsAt);

        self::assertCount(48, $slots);
        self::assertSame('2026-11-28 19:30:00', $slots[0]->startsAt->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-29 19:30:00', $slots[47]->endsAt->format('Y-m-d H:i:s'));
    }

    public function testSliceRejectsStartNotOnFullOrHalfHour(): void
    {
        $startsAt = new \DateTimeImmutable('2026-11-28 19:15:00', new \DateTimeZone('UTC'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('00 or :30');

        $this->calendar->slice($startsAt);
    }

    public function testSliceAcrossDaylightSavingTimeEndOfSummerTime(): void
    {
        // 25/10/2026: passage to winter time in Europe/Paris.
        // At 03:00 CEST, clocks go back to 02:00 CET.
        // In UTC: 2026-10-25 01:00 UTC = 03:00 CEST, then 2026-10-25 01:00 UTC = 02:00 CET.
        // A 24h edition starting at 2026-10-24 22:00 UTC (2026-10-25 00:00 CEST)
        // covers the night. All slots must be exactly 30 real minutes.
        $startsAt = new \DateTimeImmutable('2026-10-24 22:00:00', new \DateTimeZone('UTC'));

        $slots = $this->calendar->slice($startsAt);

        self::assertCount(48, $slots);

        // Verify each slot is exactly 30 minutes (1800 seconds)
        foreach ($slots as $i => $slot) {
            $duration = $slot->endsAt->getTimestamp() - $slot->startsAt->getTimestamp();
            self::assertSame(1800, $duration, \sprintf('Slot %d must be exactly 30 minutes', $i));
        }

        // The 25/10/2026 02:00 appears twice in Paris local time.
        // In UTC: 2026-10-25 00:00 UTC = 02:00 CEST (summer time)
        //           2026-10-25 01:00 UTC = 02:00 CET (winter time)
        // Both should appear in the 48 slots.
        $slotAt0000Utc = null;
        $slotAt0100Utc = null;
        foreach ($slots as $slot) {
            if ('2026-10-25 00:00:00' === $slot->startsAt->format('Y-m-d H:i:s')) {
                $slotAt0000Utc = $slot;
            }
            if ('2026-10-25 01:00:00' === $slot->startsAt->format('Y-m-d H:i:s')) {
                $slotAt0100Utc = $slot;
            }
        }

        self::assertNotNull($slotAt0000Utc, 'Slot starting at 2026-10-25 00:00 UTC (02:00 summer time) must exist');
        self::assertNotNull($slotAt0100Utc, 'Slot starting at 2026-10-25 01:00 UTC (02:00 winter time) must exist');
    }

    public function testSliceAcrossDaylightSavingTimeStartOfSummerTime(): void
    {
        // 28/03/2027: passage to summer time in Europe/Paris (Easter Sunday).
        // At 02:00 CET, clocks jump to 03:00 CEST.
        // In UTC: 2026-03-28 01:00 UTC = 02:00 CET, then 2026-03-28 01:00 UTC = 03:00 CEST.
        // The 02:00-03:00 CET hour does not exist.
        // A 24h edition starting at 2026-03-27 23:00 UTC (2026-03-28 00:00 CET)
        // covers the night. The slot from 01:00 UTC to 01:30 UTC is 02:00-02:30 CET,
        // then the next slot starts at 01:30 UTC = 03:30 CEST (since 02:00 CET never happens).
        // Wait — let me recalculate. In UTC, time is continuous. The slot boundaries are in UTC.
        // 2026-03-28 00:00 UTC = 01:00 CET (winter time, UTC+1)
        // 2026-03-28 00:30 UTC = 01:30 CET
        // 2026-03-28 01:00 UTC = 02:00 CET → but at 02:00 CET, clocks jump to 03:00 CEST
        //   So 2026-03-28 01:00 UTC = 03:00 CEST (summer time, UTC+2)
        // The slot from 00:30 UTC to 01:00 UTC is 01:30-02:00 CET (30 min real time)
        // The slot from 01:00 UTC to 01:30 UTC is 03:00-03:30 CEST (30 min real time)
        // In Paris local labels: 1h30 → 3h00 (the 2h00-3h00 hour is skipped)
        $startsAt = new \DateTimeImmutable('2027-03-27 23:00:00', new \DateTimeZone('UTC'));

        $slots = $this->calendar->slice($startsAt);

        self::assertCount(48, $slots);

        // All slots must be exactly 30 real minutes
        foreach ($slots as $i => $slot) {
            $duration = $slot->endsAt->getTimestamp() - $slot->startsAt->getTimestamp();
            self::assertSame(1800, $duration, \sprintf('Slot %d must be exactly 30 minutes', $i));
        }

        // Find the slot that starts at 2027-03-28 01:00 UTC (= 03:00 CEST, after the jump)
        $slotAfterJump = null;
        foreach ($slots as $slot) {
            if ('2027-03-28 01:00:00' === $slot->startsAt->format('Y-m-d H:i:s')) {
                $slotAfterJump = $slot;
                break;
            }
        }

        self::assertNotNull($slotAfterJump, 'Slot starting at 2027-03-28 01:00 UTC must exist');

        // In Paris local time, this slot starts at 03:00 (CEST)
        $parisTz = new \DateTimeZone('Europe/Paris');
        $localStart = $slotAfterJump->startsAt->setTimezone($parisTz);
        self::assertSame('03:00', $localStart->format('H:i'), 'The slot after the DST jump starts at 3h00 in Paris');
    }
}
