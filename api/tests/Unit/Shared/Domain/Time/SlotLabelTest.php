<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Time;

use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use PHPUnit\Framework\TestCase;

final class SlotLabelTest extends TestCase
{
    public function testLabelForNormalSlot(): void
    {
        // 2026-11-28 19:00 UTC = 2026-11-28 20:00 CET (Paris, winter time UTC+1)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-28 19:30:00', new \DateTimeZone('UTC')),
        );

        $label = SlotLabel::for($slot);

        self::assertSame('20h00–20h30', $label);
    }

    public function testLabelForSlotSpanningMidnight(): void
    {
        // 2026-11-28 23:30 UTC = 2026-11-29 00:30 CET (Paris)
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-11-28 23:30:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-29 00:00:00', new \DateTimeZone('UTC')),
        );

        $label = SlotLabel::for($slot);

        self::assertSame('0h30–1h00', $label);
    }

    public function testLabelDisambiguatesSummerTimeOnFallBack(): void
    {
        // 25/10/2026: fall-back. 02:00 appears twice in Paris.
        // 2026-10-25 00:00 UTC = 02:00 CEST (summer time, UTC+2) — first occurrence
        $slotSummer = new TimeSlot(
            new \DateTimeImmutable('2026-10-25 00:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-10-25 00:30:00', new \DateTimeZone('UTC')),
        );

        $labelSummer = SlotLabel::for($slotSummer);
        self::assertSame('2h00 (heure d’été)–2h30 (heure d’été)', $labelSummer);

        // 2026-10-25 01:00 UTC = 02:00 CET (winter time, UTC+1) — second occurrence
        $slotWinter = new TimeSlot(
            new \DateTimeImmutable('2026-10-25 01:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-10-25 01:30:00', new \DateTimeZone('UTC')),
        );

        $labelWinter = SlotLabel::for($slotWinter);
        self::assertSame('2h00 (heure d’hiver)–2h30 (heure d’hiver)', $labelWinter);
    }

    public function testLabelForSlotAfterSpringForward(): void
    {
        // 28/03/2027: spring-forward. 02:00 CET does not exist.
        // 2027-03-28 00:30 UTC = 01:30 CET (winter time)
        // 2027-03-28 01:00 UTC = 03:00 CEST (summer time) — the 02:00-03:00 hour is skipped
        $slotBefore = new TimeSlot(
            new \DateTimeImmutable('2027-03-28 00:30:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2027-03-28 01:00:00', new \DateTimeZone('UTC')),
        );

        $labelBefore = SlotLabel::for($slotBefore);
        self::assertSame('1h30–3h00', $labelBefore);

        $slotAfter = new TimeSlot(
            new \DateTimeImmutable('2027-03-28 01:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2027-03-28 01:30:00', new \DateTimeZone('UTC')),
        );

        $labelAfter = SlotLabel::for($slotAfter);
        self::assertSame('3h00–3h30', $labelAfter);
    }

    public function testLabelForSlotCrossingDstBoundary(): void
    {
        // A slot that spans the exact moment of the DST change.
        // 2026-10-25 00:30 UTC = 02:30 CEST (summer time)
        // 2026-10-25 01:00 UTC = 02:00 CET (winter time) — clocks went back at 03:00 CEST → 02:00 CET
        // Wait: the transition is at 03:00 CEST → 02:00 CET, which is 01:00 UTC.
        // So the slot 00:30–01:00 UTC spans: 02:30 CEST → 03:00 CEST (= 02:00 CET)
        // In Paris local: start = 02:30 (summer), end = 02:00 (winter) — but that's confusing.
        // Actually: 00:30 UTC = 02:30 CEST, 01:00 UTC = 02:00 CET (after fall-back)
        // The slot is 30 real minutes, but the local clock goes from 02:30 back to 02:00.
        // This is an edge case: the label should show the actual local times.
        $slot = new TimeSlot(
            new \DateTimeImmutable('2026-10-25 00:30:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-10-25 01:00:00', new \DateTimeZone('UTC')),
        );

        $label = SlotLabel::for($slot);

        // Start: 02:30 CEST (summer time) → "2h30 (heure d'été)"
        // End: 02:00 CET (winter time) → "2h00 (heure d'hiver)"
        // The label should reflect both disambiguations
        self::assertSame('2h30 (heure d’été)–2h00 (heure d’hiver)', $label);
    }
}
