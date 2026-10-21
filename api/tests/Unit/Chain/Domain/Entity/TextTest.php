<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Entity;

use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use PHPUnit\Framework\TestCase;

final class TextTest extends TestCase
{
    public function testCreateExposesAllFields(): void
    {
        $text = Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila, Le Château intérieur, I, 1'),
            new RichText('<p>Premier paragraphe.</p><p>Deuxième <strong>paragraphe</strong>.</p>'),
            'Quelles demeures le Seigneur m’invite-t-il à habiter ?',
        );

        self::assertInstanceOf(TextId::class, $text->getId());
        self::assertSame('Le château de l’âme', $text->getTitle()->value);
        self::assertSame('Sainte Thérèse d’Avila, Le Château intérieur, I, 1', $text->getSource()->value);
        self::assertSame('<p>Premier paragraphe.</p><p>Deuxième <strong>paragraphe</strong>.</p>', $text->getBody()->value);
        self::assertSame('Quelles demeures le Seigneur m’invite-t-il à habiter ?', $text->getHints());
    }

    public function testCreateWithoutHintsLeavesThemNull(): void
    {
        $text = Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila, Le Château intérieur, I, 1'),
            new RichText('<p>Texte à méditer.</p>'),
        );

        self::assertNull($text->getHints());
    }

    public function testHintsAreTrimmed(): void
    {
        $text = Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
            '  Piste de méditation  ',
        );

        self::assertSame('Piste de méditation', $text->getHints());
    }

    public function testEmptyHintsAreNormalizedToNull(): void
    {
        $text = Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
            '   ',
        );

        self::assertNull($text->getHints());
    }

    public function testCreateRejectsTooLongHints(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Les pistes de méditation ne doivent pas dépasser %d caractères.', Text::MAX_HINTS_LENGTH));

        Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
            str_repeat('a', Text::MAX_HINTS_LENGTH + 1),
        );
    }

    public function testUpdateChangesAllFields(): void
    {
        $text = Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
        );

        $text->update(
            new TextTitle('La nuit obscure'),
            new TextSource('Saint Jean de la Croix, La Nuit obscure, chant I'),
            new RichText('<p>Dans cette nuit obscure…</p>'),
            'Quels pas faire pour sortir de soi-même ?',
        );

        self::assertSame('La nuit obscure', $text->getTitle()->value);
        self::assertSame('Saint Jean de la Croix, La Nuit obscure, chant I', $text->getSource()->value);
        self::assertSame('<p>Dans cette nuit obscure…</p>', $text->getBody()->value);
        self::assertSame('Quels pas faire pour sortir de soi-même ?', $text->getHints());
    }

    public function testUpdateCanClearHints(): void
    {
        $text = Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
            'Piste de méditation',
        );

        $text->update(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
            null,
        );

        self::assertNull($text->getHints());
    }

    public function testUpdateRejectsTooLongHints(): void
    {
        $text = Text::create(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Les pistes de méditation ne doivent pas dépasser %d caractères.', Text::MAX_HINTS_LENGTH));

        $text->update(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            new RichText('<p>Texte.</p>'),
            str_repeat('a', Text::MAX_HINTS_LENGTH + 1),
        );
    }

    public function testHintsBoundIsPublishedAsConstant(): void
    {
        self::assertSame(2000, Text::MAX_HINTS_LENGTH);
    }
}
