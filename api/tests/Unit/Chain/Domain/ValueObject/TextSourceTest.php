<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\ValueObject;

use App\Chain\Domain\ValueObject\TextSource;
use PHPUnit\Framework\TestCase;

final class TextSourceTest extends TestCase
{
    public function testItAcceptsValidSource(): void
    {
        $source = new TextSource('Sainte Thérèse de Lisieux, Histoire d’une âme');

        self::assertSame('Sainte Thérèse de Lisieux, Histoire d’une âme', $source->value);
    }

    public function testItTrimsWhitespace(): void
    {
        $source = new TextSource('  Psaume 23  ');

        self::assertSame('Psaume 23', $source->value);
    }

    public function testItRejectsEmptySource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La source est obligatoire.');

        new TextSource('');
    }

    public function testItRejectsWhitespaceOnlySource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La source est obligatoire.');

        new TextSource('   ');
    }

    public function testItRejectsTooLongSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('La source ne doit pas dépasser %d caractères.', TextSource::MAX_LENGTH));

        new TextSource(str_repeat('a', TextSource::MAX_LENGTH + 1));
    }

    public function testItAcceptsMaxLengthSource(): void
    {
        $source = new TextSource(str_repeat('a', TextSource::MAX_LENGTH));

        self::assertSame(TextSource::MAX_LENGTH, mb_strlen($source->value));
    }

    public function testBoundsArePublishedAsConstants(): void
    {
        self::assertSame(1, TextSource::MIN_LENGTH);
        self::assertSame(255, TextSource::MAX_LENGTH);
        self::assertSame(1, TextSource::getMinLength());
        self::assertSame(255, TextSource::getMaxLength());
    }
}
