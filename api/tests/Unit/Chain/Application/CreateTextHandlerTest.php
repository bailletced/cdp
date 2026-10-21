<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class CreateTextHandlerTest extends TestCase
{
    public function testItCreatesATextWithSanitizedBody(): void
    {
        $repository = new InMemoryTextRepository();
        $handler = new CreateTextHandler($repository, new SymfonyHtmlSanitizer());

        $result = ($handler)(new CreateText(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila, Le Château intérieur, I, 1'),
            '<p>Texte <strong>gras</strong> et <em>italique</em>.</p><script>alert(1)</script>',
            'Piste de méditation',
        ));

        self::assertNotNull($result->textId);
        $text = $repository->findById($result->textId);
        self::assertNotNull($text);
        self::assertSame('Le château de l’âme', $text->getTitle()->value);
        self::assertSame('Sainte Thérèse d’Avila, Le Château intérieur, I, 1', $text->getSource()->value);
        self::assertSame('<p>Texte <strong>gras</strong> et <em>italique</em>.</p>', $text->getBody()->value);
        self::assertSame('Piste de méditation', $text->getHints());
    }

    public function testItRejectsBodyThatIsEmptyAfterSanitizing(): void
    {
        $repository = new InMemoryTextRepository();
        $handler = new CreateTextHandler($repository, new SymfonyHtmlSanitizer());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le corps du texte est obligatoire.');

        ($handler)(new CreateText(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            '<script>alert(1)</script>',
        ));
    }
}
