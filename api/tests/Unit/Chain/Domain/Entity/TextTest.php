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
        );

        self::assertInstanceOf(TextId::class, $text->getId());
        self::assertSame('Le château de l’âme', $text->getTitle()->value);
        self::assertSame('Sainte Thérèse d’Avila, Le Château intérieur, I, 1', $text->getSource()->value);
        self::assertSame('<p>Premier paragraphe.</p><p>Deuxième <strong>paragraphe</strong>.</p>', $text->getBody()->value);
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
        );

        self::assertSame('La nuit obscure', $text->getTitle()->value);
        self::assertSame('Saint Jean de la Croix, La Nuit obscure, chant I', $text->getSource()->value);
        self::assertSame('<p>Dans cette nuit obscure…</p>', $text->getBody()->value);
    }
}
