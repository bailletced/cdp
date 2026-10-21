<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\ValueObject;

use App\Chain\Domain\ValueObject\Presentation;
use PHPUnit\Framework\TestCase;

final class PresentationTest extends TestCase
{
    public function testItAcceptsValidPresentation(): void
    {
        $presentation = new Presentation('Une chaîne de prière ininterrompue.');

        self::assertSame('Une chaîne de prière ininterrompue.', $presentation->value);
    }

    public function testItAcceptsMultilineText(): void
    {
        $text = "Ligne 1\nLigne 2\nLigne 3";
        $presentation = new Presentation($text);

        self::assertSame($text, $presentation->value);
    }

    public function testItTrimsWhitespace(): void
    {
        $presentation = new Presentation('  Du texte  ');

        self::assertSame('Du texte', $presentation->value);
    }

    public function testItAcceptsEmptyPresentation(): void
    {
        $presentation = new Presentation('');

        self::assertSame('', $presentation->value);
    }

    public function testItRejectsTooLongPresentation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Presentation(str_repeat('a', Presentation::MAX_LENGTH + 1));
    }

    public function testItAcceptsMaxLengthPresentation(): void
    {
        $presentation = new Presentation(str_repeat('a', Presentation::MAX_LENGTH));

        self::assertSame(Presentation::MAX_LENGTH, mb_strlen($presentation->value));
    }

    public function testBoundsArePublishedAsConstants(): void
    {
        self::assertSame(0, Presentation::MIN_LENGTH);
        self::assertSame(2000, Presentation::MAX_LENGTH);
        self::assertSame(0, Presentation::getMinLength());
        self::assertSame(2000, Presentation::getMaxLength());
    }
}
