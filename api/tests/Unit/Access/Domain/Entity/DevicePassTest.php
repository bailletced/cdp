<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Domain\Entity;

use App\Access\Domain\Entity\DevicePass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DevicePassTest extends TestCase
{
    private const string TOKEN = 'a-random-256-bit-token';

    public function testOnlyTheSha256HashOfTheTokenIsKept(): void
    {
        $pass = new DevicePass(self::TOKEN, [], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00'));

        self::assertSame(hash('sha256', self::TOKEN), $pass->getTokenHash());
        self::assertSame(DevicePass::hashToken(self::TOKEN), $pass->getTokenHash());
        self::assertStringNotContainsString(self::TOKEN, serialize($pass));
    }

    public function testItCoversTheInscriptionsMadeFromTheDevice(): void
    {
        $first = Uuid::v7()->toRfc4122();
        $now = new \DateTimeImmutable('2026-11-20 10:00');
        $expiresAt = new \DateTimeImmutable('2026-12-06 20:00');

        $pass = new DevicePass(self::TOKEN, [$first], $expiresAt, $now);

        self::assertSame([$first], $pass->getInscriptionIds());
        self::assertEquals($expiresAt, $pass->getExpiresAt());
        self::assertEquals($now, $pass->getLastSeenAt());
    }

    public function testANewInscriptionIsAddedToThePassOnce(): void
    {
        $first = Uuid::v7()->toRfc4122();
        $second = Uuid::v7()->toRfc4122();
        $pass = new DevicePass(self::TOKEN, [$first], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00'));

        $pass->cover([$second, $first], new \DateTimeImmutable('2026-12-06 20:00'));

        self::assertSame([$first, $second], $pass->getInscriptionIds());
    }

    public function testCoveringALaterEditionPushesTheExpiryBackNeverForward(): void
    {
        $pass = new DevicePass(self::TOKEN, [], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00'));

        $pass->cover([Uuid::v7()->toRfc4122()], new \DateTimeImmutable('2027-04-10 20:00'));
        self::assertEquals(new \DateTimeImmutable('2027-04-10 20:00'), $pass->getExpiresAt());

        $pass->cover([Uuid::v7()->toRfc4122()], new \DateTimeImmutable('2026-12-06 20:00'));
        self::assertEquals(new \DateTimeImmutable('2027-04-10 20:00'), $pass->getExpiresAt());
    }

    public function testItExpiresAtItsExpiryInstant(): void
    {
        $pass = new DevicePass(self::TOKEN, [], new \DateTimeImmutable('2026-12-06 20:00:00'), new \DateTimeImmutable('2026-11-20 10:00'));

        self::assertFalse($pass->isExpiredAt(new \DateTimeImmutable('2026-12-06 19:59:59')));
        self::assertTrue($pass->isExpiredAt(new \DateTimeImmutable('2026-12-06 20:00:00')));
    }

    public function testItRemembersWhenTheDeviceWasLastSeen(): void
    {
        $pass = new DevicePass(self::TOKEN, [], new \DateTimeImmutable('2026-12-06 20:00'), new \DateTimeImmutable('2026-11-20 10:00'));

        $pass->seenAt(new \DateTimeImmutable('2026-11-28 21:15'));

        self::assertEquals(new \DateTimeImmutable('2026-11-28 21:15'), $pass->getLastSeenAt());
    }
}
