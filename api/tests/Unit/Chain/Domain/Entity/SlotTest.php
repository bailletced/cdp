<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Entity;

use App\Chain\Domain\Entity\Slot;
use App\Chain\Domain\ValueObject\SlotId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class SlotTest extends TestCase
{
    public function testSlotHasRankStartsAtAndEndsAt(): void
    {
        $startsAt = new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC'));
        $endsAt = new \DateTimeImmutable('2026-11-28 19:30:00', new \DateTimeZone('UTC'));

        $slot = new Slot(1, $startsAt, $endsAt);

        self::assertSame(1, $slot->getRank());
        self::assertSame($startsAt, $slot->getStartsAt());
        self::assertSame($endsAt, $slot->getEndsAt());
    }

    public function testSlotHasUuidV7Identity(): void
    {
        $startsAt = new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC'));
        $endsAt = new \DateTimeImmutable('2026-11-28 19:30:00', new \DateTimeZone('UTC'));

        $slot = new Slot(1, $startsAt, $endsAt);
        $other = new Slot(2, $startsAt, $endsAt);

        self::assertInstanceOf(SlotId::class, $slot->getId());
        self::assertInstanceOf(UuidV7::class, $slot->getId()->value);
        self::assertFalse($slot->getId()->equals($other->getId()));
    }
}
