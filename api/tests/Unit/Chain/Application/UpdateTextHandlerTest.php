<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Application\UpdateText;
use App\Chain\Application\UpdateTextHandler;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class UpdateTextHandlerTest extends TestCase
{
    private InMemoryTextRepository $repository;

    private CreateTextHandler $createHandler;

    private UpdateTextHandler $updateHandler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryTextRepository();
        $sanitizer = new SymfonyHtmlSanitizer();
        $this->createHandler = new CreateTextHandler($this->repository, $sanitizer);
        $this->updateHandler = new UpdateTextHandler($this->repository, $sanitizer);
    }

    public function testItUpdatesAText(): void
    {
        $createResult = ($this->createHandler)(new CreateText(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            '<p>Texte initial.</p>',
        ));

        ($this->updateHandler)(new UpdateText(
            $createResult->textId,
            new TextTitle('La nuit obscure'),
            new TextSource('Saint Jean de la Croix, La Nuit obscure, chant I'),
            '<p>Texte corrigé <em>mis en forme</em>.</p><script>alert(1)</script>',
            'Nouvelle piste',
        ));

        $text = $this->repository->findById($createResult->textId);
        self::assertNotNull($text);
        self::assertSame('La nuit obscure', $text->getTitle()->value);
        self::assertSame('Saint Jean de la Croix, La Nuit obscure, chant I', $text->getSource()->value);
        self::assertSame('<p>Texte corrigé <em>mis en forme</em>.</p>', $text->getBody()->value);
        self::assertSame('Nouvelle piste', $text->getHints());
    }

    public function testUpdateThrowsWhenTextIsUnknown(): void
    {
        $this->expectException(TextNotFoundException::class);

        ($this->updateHandler)(new UpdateText(
            new TextId(),
            new TextTitle('La nuit obscure'),
            new TextSource('Saint Jean de la Croix'),
            '<p>Texte.</p>',
            null,
        ));
    }

    public function testItRejectsBodyThatIsEmptyAfterSanitizing(): void
    {
        $createResult = ($this->createHandler)(new CreateText(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            '<p>Texte initial.</p>',
        ));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le corps du texte est obligatoire.');

        ($this->updateHandler)(new UpdateText(
            $createResult->textId,
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            '<img src="x" onerror="alert(1)">',
            null,
        ));
    }
}
