<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Application;

use App\Access\Application\PresentDevicePass;
use App\Access\Application\PresentDevicePassHandler;
use App\Access\Domain\Entity\DevicePass;
use App\Access\Infrastructure\InMemory\Repository\InMemoryDevicePassRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Uuid;

final class PresentDevicePassHandlerTest extends TestCase
{
    private const string NOW = '2026-11-28 21:15:00';

    private InMemoryDevicePassRepository $passes;

    private PresentDevicePassHandler $handler;

    protected function setUp(): void
    {
        $this->passes = new InMemoryDevicePassRepository();
        $this->handler = new PresentDevicePassHandler($this->passes, new MockClock(self::NOW, 'UTC'));
    }

    public function testAValidPassGivesItsInscriptionsAndIsSeenNow(): void
    {
        $inscriptionId = Uuid::v7()->toRfc4122();
        $this->passes->add(new DevicePass('device-token', [$inscriptionId], $this->utc('2026-12-06 19:00:00'), $this->utc('2026-11-20 10:00:00')));

        $covered = ($this->handler)(new PresentDevicePass('device-token'));

        self::assertSame([$inscriptionId], $covered);
        self::assertEquals($this->utc(self::NOW), $this->passes->findByToken('device-token')?->getLastSeenAt());
    }

    public function testAnExpiredPassGivesNothing(): void
    {
        $this->passes->add(new DevicePass('device-token', [Uuid::v7()->toRfc4122()], $this->utc(self::NOW), $this->utc('2026-11-20 10:00:00')));

        self::assertSame([], ($this->handler)(new PresentDevicePass('device-token')));
    }

    public function testAnUnknownTokenGivesNothing(): void
    {
        self::assertSame([], ($this->handler)(new PresentDevicePass('forged-token')));
    }

    private function utc(string $instant): \DateTimeImmutable
    {
        return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
    }
}
