<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Clock;

use App\Shared\Infrastructure\Clock\MockClock;
use App\Shared\Infrastructure\Clock\SystemClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

/**
 * Test pour F00-T17 : Horloge PSR-20.
 */
final class ClockTest extends TestCase
{
    public function testSystemClockImplementsClockInterface(): void
    {
        $clock = new SystemClock();

        $this->assertInstanceOf(ClockInterface::class, $clock);
    }

    public function testSystemClockNowReturnsDateTimeImmutable(): void
    {
        $clock = new SystemClock();
        $dateTime = $clock->now();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
    }

    public function testSystemClockCurrentTimestampReturnsTimestamp(): void
    {
        $clock = new SystemClock();
        $timestamp = $clock->currentTimestamp();

        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);
    }

    public function testMockClockImplementsClockInterface(): void
    {
        $clock = new MockClock();

        $this->assertInstanceOf(ClockInterface::class, $clock);
    }

    public function testMockClockNowReturnsFixedDateTime(): void
    {
        $timestamp = 1700000000;
        $clock = new MockClock($timestamp);
        $dateTime = $clock->now();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertSame($timestamp, $dateTime->getTimestamp());
    }

    public function testMockClockCurrentTimestampReturnsFixedTimestamp(): void
    {
        $timestamp = 1700000000;
        $clock = new MockClock($timestamp);

        $this->assertSame($timestamp, $clock->currentTimestamp());
    }

    public function testMockClockCanBeUpdated(): void
    {
        $clock = new MockClock(1000);
        $this->assertSame(1000, $clock->currentTimestamp());

        $clock->setTimestamp(2000);
        $this->assertSame(2000, $clock->currentTimestamp());
    }

    public function testMockClockCanBeUpdatedWithDateTime(): void
    {
        $initialDateTime = new DateTimeImmutable('@1000');
        $clock = new MockClock($initialDateTime);
        $this->assertSame(1000, $clock->currentTimestamp());

        $newDateTime = new DateTimeImmutable('@2000');
        $clock->setDateTime($newDateTime);
        $this->assertSame(2000, $clock->currentTimestamp());
    }
}
