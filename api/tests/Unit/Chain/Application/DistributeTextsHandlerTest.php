<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\DistributeTexts;
use App\Chain\Application\DistributeTextsHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class DistributeTextsHandlerTest extends TestCase
{
    public function testItDistributesTextsFromTheGivenRank(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);

        $text1 = self::createText('Texte un');
        $text2 = self::createText('Texte deux');
        $textRepository->add($text1);
        $textRepository->add($text2);

        $handler = new DistributeTextsHandler($editionRepository, $textRepository);

        ($handler)(new DistributeTexts($edition->getId(), [$text1->getId(), $text2->getId()], 1));

        $slots = $edition->getSlots();
        self::assertTrue($slots[0]->getTextId()?->equals($text1->getId()->value));
        self::assertTrue($slots[1]->getTextId()?->equals($text2->getId()->value));
        self::assertNull($slots[2]->getTextId());
    }

    public function testItTruncatesTextsBeyondSlot48(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);

        $textIds = [];
        for ($i = 0; $i < 10; ++$i) {
            $text = self::createText('Texte '.$i);
            $textRepository->add($text);
            $textIds[] = $text->getId();
        }

        $handler = new DistributeTextsHandler($editionRepository, $textRepository);

        ($handler)(new DistributeTexts($edition->getId(), $textIds, 40));

        $slots = $edition->getSlots();
        // Slots 40–48 (ranks 40–48) are filled: 9 slots
        self::assertTrue($slots[39]->getTextId()?->equals($textIds[0]->value));
        self::assertTrue($slots[47]->getTextId()?->equals($textIds[8]->value));
        self::assertCount(48, $slots);
    }

    public function testItThrowsWhenAnEditionDoesNotExist(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();

        $handler = new DistributeTextsHandler($editionRepository, $textRepository);

        $this->expectException(EditionNotFoundException::class);

        ($handler)(new DistributeTexts(new \App\Chain\Domain\ValueObject\EditionId(), [new TextId()], 1));
    }

    public function testItThrowsWhenATextDoesNotExist(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);

        $handler = new DistributeTextsHandler($editionRepository, $textRepository);

        $this->expectException(TextNotFoundException::class);

        ($handler)(new DistributeTexts($edition->getId(), [new TextId()], 1));
    }

    public function testItAcceptsAnEmptyTextList(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);

        $handler = new DistributeTextsHandler($editionRepository, $textRepository);

        ($handler)(new DistributeTexts($edition->getId(), [], 1));

        foreach ($edition->getSlots() as $slot) {
            self::assertNull($slot->getTextId());
        }
    }

    private static function createEdition(): Edition
    {
        return Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
    }

    private static function createText(string $title): Text
    {
        return Text::create(
            new TextTitle($title),
            new TextSource('Auteur'),
            new RichText('<p>Corps.</p>'),
        );
    }
}
