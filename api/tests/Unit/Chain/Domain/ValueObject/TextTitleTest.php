<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\ValueObject;

use App\Chain\Domain\ValueObject\TextTitle;
use PHPUnit\Framework\TestCase;

final class TextTitleTest extends TestCase
{
    public function testItAcceptsValidTitle(): void
    {
        $title = new TextTitle('Le château de l’âme');

        self::assertSame('Le château de l’âme', $title->value);
    }

    public function testItTrimsWhitespace(): void
    {
        $title = new TextTitle('  Le château de l’âme  ');

        self::assertSame('Le château de l’âme', $title->value);
    }

    public function testItRejectsEmptyTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextTitle('');
    }

    public function testItRejectsWhitespaceOnlyTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextTitle('   ');
    }

    public function testItRejectsTooShortTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextTitle('ab');
    }

    public function testItRejectsTooLongTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextTitle(str_repeat('a', TextTitle::MAX_LENGTH + 1));
    }

    public function testItAcceptsMinLengthTitle(): void
    {
        $title = new TextTitle(str_repeat('a', TextTitle::MIN_LENGTH));

        self::assertSame(TextTitle::MIN_LENGTH, mb_strlen($title->value));
    }

    public function testItAcceptsMaxLengthTitle(): void
    {
        $title = new TextTitle(str_repeat('a', TextTitle::MAX_LENGTH));

        self::assertSame(TextTitle::MAX_LENGTH, mb_strlen($title->value));
    }

    public function testBoundsArePublishedAsConstants(): void
    {
        self::assertSame(3, TextTitle::MIN_LENGTH);
        self::assertSame(120, TextTitle::MAX_LENGTH);
        self::assertSame(3, TextTitle::getMinLength());
        self::assertSame(120, TextTitle::getMaxLength());
    }
}
