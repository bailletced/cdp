<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\ValueObject;

use App\Chain\Domain\ValueObject\EditionId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class EditionIdTest extends TestCase
{
    public function testItGeneratesUuidV7ByDefault(): void
    {
        $id = new EditionId();

        self::assertInstanceOf(Uuid::class, $id->value);
        self::assertInstanceOf(UuidV7::class, $id->value);
    }

    public function testItAcceptsAUuidString(): void
    {
        $uuid = Uuid::v7();
        $id = new EditionId($uuid->toRfc4122());

        self::assertTrue($id->value->equals($uuid));
    }

    public function testItAcceptsAUuidInstance(): void
    {
        $uuid = Uuid::v7();
        $id = new EditionId($uuid);

        self::assertSame($uuid, $id->value);
    }

    public function testEquals(): void
    {
        $uuid = Uuid::v7();
        $id1 = new EditionId($uuid);
        $id2 = new EditionId($uuid);
        $id3 = new EditionId();

        self::assertTrue($id1->equals($id2));
        self::assertFalse($id1->equals($id3));
    }

    public function testToString(): void
    {
        $uuid = Uuid::v7();
        $id = new EditionId($uuid);

        self::assertSame($uuid->toRfc4122(), (string) $id);
    }
}
