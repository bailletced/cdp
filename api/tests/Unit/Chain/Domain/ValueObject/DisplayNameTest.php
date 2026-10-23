<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\ValueObject;

use App\Chain\Domain\ValueObject\DisplayName;
use PHPUnit\Framework\TestCase;

final class DisplayNameTest extends TestCase
{
    public function testItAcceptsAValidDisplayName(): void
    {
        $name = new DisplayName('Marie Dupont');

        self::assertSame('Marie Dupont', $name->value);
    }

    public function testItAcceptsAFamilyName(): void
    {
        $name = new DisplayName('Famille Martin');

        self::assertSame('Famille Martin', $name->value);
    }

    public function testItTrimsWhitespace(): void
    {
        $name = new DisplayName('  Marie Dupont  ');

        self::assertSame('Marie Dupont', $name->value);
    }

    public function testItRejectsEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DisplayName('');
    }

    public function testItRejectsWhitespaceOnlyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DisplayName('   ');
    }

    public function testItRejectsTooLongName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DisplayName(str_repeat('a', DisplayName::MAX_LENGTH + 1));
    }

    public function testItAcceptsMinLengthName(): void
    {
        $name = new DisplayName('a');

        self::assertSame(1, mb_strlen($name->value));
    }

    public function testItAcceptsMaxLengthName(): void
    {
        $name = new DisplayName(str_repeat('a', DisplayName::MAX_LENGTH));

        self::assertSame(DisplayName::MAX_LENGTH, mb_strlen($name->value));
    }

    public function testItCountsMultibyteCharacters(): void
    {
        $name = new DisplayName(str_repeat('é', DisplayName::MAX_LENGTH));

        self::assertSame(DisplayName::MAX_LENGTH, mb_strlen($name->value));
    }

    public function testBoundsArePublishedAsConstants(): void
    {
        self::assertSame(1, DisplayName::MIN_LENGTH);
        self::assertSame(80, DisplayName::MAX_LENGTH);
        self::assertSame(1, DisplayName::getMinLength());
        self::assertSame(80, DisplayName::getMaxLength());
    }
}
