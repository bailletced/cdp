<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Application;

use App\Access\Application\GrantDevicePass;
use App\Access\Application\GrantDevicePassHandler;
use App\Access\Infrastructure\InMemory\Repository\InMemoryDevicePassRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Uuid;

final class GrantDevicePassHandlerTest extends TestCase
{
    private InMemoryDevicePassRepository $passes;

    private GrantDevicePassHandler $handler;

    private \DateTimeImmutable $expiresAt;

    protected function setUp(): void
    {
        $this->passes = new InMemoryDevicePassRepository();
        $this->handler = new GrantDevicePassHandler($this->passes, new MockClock('2026-11-20 10:00:00', 'UTC'));
        $this->expiresAt = new \DateTimeImmutable('2026-12-06 20:00:00', new \DateTimeZone('UTC'));
    }

    public function testADeviceWithoutPassGetsANewOne(): void
    {
        $inscriptionId = Uuid::v7()->toRfc4122();

        $token = ($this->handler)(new GrantDevicePass(null, 'fresh-token', [$inscriptionId], $this->expiresAt));

        self::assertSame('fresh-token', $token);
        $pass = $this->passes->findByToken('fresh-token');
        self::assertNotNull($pass);
        self::assertSame([$inscriptionId], $pass->getInscriptionIds());
        self::assertEquals($this->expiresAt, $pass->getExpiresAt());
    }

    public function testTwoSuccessiveInscriptionsEnrichTheSamePass(): void
    {
        $first = Uuid::v7()->toRfc4122();
        $second = Uuid::v7()->toRfc4122();
        $token = ($this->handler)(new GrantDevicePass(null, 'first-token', [$first], $this->expiresAt));

        $kept = ($this->handler)(new GrantDevicePass($token, 'second-token', [$second], $this->expiresAt));

        self::assertSame('first-token', $kept);
        self::assertSame([$first, $second], $this->passes->findByToken('first-token')?->getInscriptionIds());
        self::assertNull($this->passes->findByToken('second-token'));
    }

    public function testAnUnknownTokenIsReplacedByANewPass(): void
    {
        $token = ($this->handler)(new GrantDevicePass('forged-token', 'fresh-token', [Uuid::v7()->toRfc4122()], $this->expiresAt));

        self::assertSame('fresh-token', $token);
        self::assertNull($this->passes->findByToken('forged-token'));
    }

    public function testAnExpiredPassIsNeverExtended(): void
    {
        $old = Uuid::v7()->toRfc4122();
        ($this->handler)(new GrantDevicePass(null, 'old-token', [$old], new \DateTimeImmutable('2026-11-01 20:00:00', new \DateTimeZone('UTC'))));

        $token = ($this->handler)(new GrantDevicePass('old-token', 'fresh-token', [Uuid::v7()->toRfc4122()], $this->expiresAt));

        self::assertSame('fresh-token', $token);
        self::assertSame([$old], $this->passes->findByToken('old-token')?->getInscriptionIds());
    }
}
