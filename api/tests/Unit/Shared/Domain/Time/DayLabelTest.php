<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Time;

use App\Shared\Domain\Time\DayLabel;
use PHPUnit\Framework\TestCase;

final class DayLabelTest extends TestCase
{
    public function testItNamesTheParisDayOfAnInstant(): void
    {
        self::assertSame('samedi 28 novembre', DayLabel::for(new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC'))));
    }

    public function testAfterMidnightInParisItIsAlreadyTheNextDay(): void
    {
        // 23:30 UTC on Saturday is 0h30 on Sunday in Paris
        self::assertSame('dimanche 29 novembre', DayLabel::for(new \DateTimeImmutable('2026-11-28 23:30:00', new \DateTimeZone('UTC'))));
    }
}
