<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Domain\ValueObject;

use App\Access\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AccountIdTest extends TestCase
{
    public function testCreateWithUuid(): void
    {
        $uuid = Uuid::v7();
        $accountId = new AccountId($uuid);

        $this->assertSame($uuid, $accountId->value);
    }

    public function testCreateWithString(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $accountId = new AccountId($uuidString);

        $this->assertEquals($uuidString, $accountId->value->toRfc4122());
    }

    public function testCreateWithNullGeneratesUuidV7(): void
    {
        $accountId = new AccountId();

        $this->assertInstanceOf(Uuid::class, $accountId->value);
        $this->assertInstanceOf(\Symfony\Component\Uid\UuidV7::class, $accountId->value);
    }

    public function testEquals(): void
    {
        $uuid = Uuid::v7();
        $accountId1 = new AccountId($uuid);
        $accountId2 = new AccountId($uuid);

        $this->assertTrue($accountId1->equals($accountId2));
    }

    public function testNotEquals(): void
    {
        $accountId1 = new AccountId();
        $accountId2 = new AccountId();

        $this->assertFalse($accountId1->equals($accountId2));
    }

    public function testToString(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $accountId = new AccountId($uuidString);

        $this->assertEquals($uuidString, (string) $accountId);
    }
}
