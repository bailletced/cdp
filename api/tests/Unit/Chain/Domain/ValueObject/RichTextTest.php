<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\ValueObject;

use App\Chain\Domain\ValueObject\RichText;
use PHPUnit\Framework\TestCase;

final class RichTextTest extends TestCase
{
    public function testItAcceptsSanitizedHtml(): void
    {
        $body = new RichText('<p>Un <strong>texte</strong> à méditer.</p>');

        self::assertSame('<p>Un <strong>texte</strong> à méditer.</p>', $body->value);
    }

    public function testItTrimsSurroundingWhitespace(): void
    {
        $body = new RichText("  <p>texte</p>\n");

        self::assertSame('<p>texte</p>', $body->value);
    }

    public function testItRejectsEmptyBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RichText('');
    }

    public function testItRejectsWhitespaceOnlyBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RichText('   ');
    }

    public function testItRejectsTagsOnlyBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RichText('<p></p>');
    }

    public function testItRejectsNonBreakingSpaceOnlyBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RichText('<p>&nbsp;</p>');
    }

    public function testItAcceptsEscapedMarkupAsVisibleContent(): void
    {
        $body = new RichText('<p>&lt;p&gt;</p>');

        self::assertSame('<p>&lt;p&gt;</p>', $body->value);
    }

    public function testItRejectsTooLongBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RichText(str_repeat('a', RichText::MAX_LENGTH + 1));
    }

    public function testItAcceptsMaxLengthBody(): void
    {
        $body = new RichText(str_repeat('a', RichText::MAX_LENGTH));

        self::assertSame(RichText::MAX_LENGTH, mb_strlen($body->value));
    }

    public function testItPublishesMaxLengthAsConstant(): void
    {
        self::assertSame(10_000, RichText::MAX_LENGTH);
    }
}
