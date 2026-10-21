<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Application\DeleteText;
use App\Chain\Application\DeleteTextHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Exception\TextAttributedToScheduledEditionException;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class DeleteTextHandlerTest extends TestCase
{
    private InMemoryTextRepository $textRepository;

    private InMemoryEditionRepository $editionRepository;

    private CreateTextHandler $createHandler;

    private DeleteTextHandler $deleteHandler;

    protected function setUp(): void
    {
        $this->textRepository = new InMemoryTextRepository();
        $this->editionRepository = new InMemoryEditionRepository();
        $sanitizer = new SymfonyHtmlSanitizer();
        $this->createHandler = new CreateTextHandler($this->textRepository, $sanitizer);
        $this->deleteHandler = new DeleteTextHandler($this->textRepository, $this->editionRepository);
    }

    public function testItDeletesAnUnattributedText(): void
    {
        $createResult = ($this->createHandler)(new CreateText(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            '<p>Texte.</p>',
        ));

        ($this->deleteHandler)(new DeleteText($createResult->textId));

        self::assertNull($this->textRepository->findById($createResult->textId));
    }

    public function testItRefusesToDeleteATextAttributedToAScheduledEdition(): void
    {
        $createResult = ($this->createHandler)(new CreateText(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            '<p>Texte.</p>',
        ));
        $edition = $this->createEdition();
        $edition->schedule(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC')));
        $edition->getSlots()[0]->setTextId($createResult->textId->value);
        $this->editionRepository->add($edition);

        try {
            ($this->deleteHandler)(new DeleteText($createResult->textId));
            self::fail('Expected TextAttributedToScheduledEditionException was not thrown.');
        } catch (TextAttributedToScheduledEditionException) {
            // RG-TX-3: the refusal is expected; the text and its attribution survive.
        }

        self::assertNotNull($this->textRepository->findById($createResult->textId));
        self::assertSame($createResult->textId->value, $edition->getSlots()[0]->getTextId());
    }

    public function testItDeletesATextAttributedToADraftEditionAndWithdrawsAttributions(): void
    {
        $createResult = ($this->createHandler)(new CreateText(
            new TextTitle('Le château de l’âme'),
            new TextSource('Sainte Thérèse d’Avila'),
            '<p>Texte.</p>',
        ));
        $edition = $this->createEdition();
        $edition->getSlots()[0]->setTextId($createResult->textId->value);
        $edition->getSlots()[1]->setTextId($createResult->textId->value);
        $this->editionRepository->add($edition);

        ($this->deleteHandler)(new DeleteText($createResult->textId));

        self::assertNull($this->textRepository->findById($createResult->textId));
        self::assertNull($edition->getSlots()[0]->getTextId());
        self::assertNull($edition->getSlots()[1]->getTextId());
    }

    public function testDeleteThrowsWhenTextIsUnknown(): void
    {
        $this->expectException(TextNotFoundException::class);

        ($this->deleteHandler)(new DeleteText(new TextId()));
    }

    private function createEdition(): Edition
    {
        return Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
    }
}
