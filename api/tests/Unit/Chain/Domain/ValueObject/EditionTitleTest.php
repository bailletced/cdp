<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\ValueObject;

use App\Chain\Domain\ValueObject\EditionTitle;
use PHPUnit\Framework\TestCase;

final class EditionTitleTest extends TestCase
{
    public function testItAcceptsValidTitle(): void
    {
        $title = new EditionTitle('24 heures de prière');

        self::assertSame('24 heures de prière', $title->value);
    }

    public function testItTrimsWhitespace(): void
    {
        $title = new EditionTitle('  24 heures de prière  ');

        self::assertSame('24 heures de prière', $title->value);
    }

    public function testItRejectsEmptyTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EditionTitle('');
    }

    public function testItRejectsWhitespaceOnlyTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EditionTitle('   ');
    }

    public function testItRejectsTooShortTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EditionTitle('ab');
    }

    public function testItRejectsTooLongTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EditionTitle(str_repeat('a', EditionTitle::MAX_LENGTH + 1));
    }

    public function testItAcceptsMinLengthTitle(): void
    {
        $title = new EditionTitle(str_repeat('a', EditionTitle::MIN_LENGTH));

        self::assertSame(EditionTitle::MIN_LENGTH, mb_strlen($title->value));
    }

    public function testItAcceptsMaxLengthTitle(): void
    {
        $title = new EditionTitle(str_repeat('a', EditionTitle::MAX_LENGTH));

        self::assertSame(EditionTitle::MAX_LENGTH, mb_strlen($title->value));
    }

    public function testBoundsArePublishedAsConstants(): void
    {
        self::assertSame(3, EditionTitle::MIN_LENGTH);
        self::assertSame(120, EditionTitle::MAX_LENGTH);
        self::assertSame(3, EditionTitle::getMinLength());
        self::assertSame(120, EditionTitle::getMaxLength());
    }
}
