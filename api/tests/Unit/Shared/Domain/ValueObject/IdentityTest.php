<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Identifier;
use App\Shared\Domain\ValueObject\Identity;
use App\Tests\Unit\Shared\Domain\ValueObject\Fixture\SampleId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class IdentityTest extends TestCase
{
    public function testTwoIdentitiesAreEqualWhenTheyHoldTheSameValue(): void
    {
        $id = new SampleId('0190b2a0-0000-7000-8000-000000000001');

        self::assertTrue($id->equals(new SampleId('0190b2a0-0000-7000-8000-000000000001')));
        self::assertFalse($id->equals(new SampleId('0190b2a0-0000-7000-8000-000000000002')));
    }

    public function testItCanBeRebuiltFromItsStringForm(): void
    {
        $id = new class('0190b2a0-0000-7000-8000-000000000001') implements Identifier {
            use Identity;
        };

        self::assertSame('0190b2a0-0000-7000-8000-000000000001', $id->value->toRfc4122());
    }

    public function testItGeneratesAUuidV7ByDefault(): void
    {
        $id = new class implements Identifier {
            use Identity;
        };

        self::assertInstanceOf(UuidV7::class, $id->value);
    }
}
