<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\AssignText;
use App\Chain\Application\AssignTextHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\TextNotFoundException;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class AssignTextHandlerTest extends TestCase
{
    public function testItAssignsATextToASlot(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);
        $text = self::createText();
        $textRepository->add($text);

        $handler = new AssignTextHandler($editionRepository, $textRepository);
        $slot = $edition->getSlots()[0];

        ($handler)(new AssignText($slot->getId(), $text->getId()));

        self::assertTrue($slot->getTextId()?->equals($text->getId()->value));
    }

    public function testItWithdrawsATextWhenTextIdIsNull(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);
        $slot = $edition->getSlots()[0];
        $slot->setTextId(new TextId()->value);

        $handler = new AssignTextHandler($editionRepository, $textRepository);

        ($handler)(new AssignText($slot->getId(), null));

        self::assertNull($slot->getTextId());
    }

    public function testItThrowsWhenTheTextDoesNotExist(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);

        $handler = new AssignTextHandler($editionRepository, $textRepository);

        $this->expectException(TextNotFoundException::class);

        ($handler)(new AssignText($edition->getSlots()[0]->getId(), new TextId()));
    }

    public function testItThrowsWhenTheSlotDoesNotBelongToAnyEdition(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $edition = self::createEdition();
        $editionRepository->add($edition);
        $text = self::createText();
        $textRepository->add($text);

        $handler = new AssignTextHandler($editionRepository, $textRepository);

        $this->expectException(EditionNotFoundException::class);

        ($handler)(new AssignText(new SlotId(), $text->getId()));
    }

    public function testItThrowsWhenNoEditionContainsTheSlot(): void
    {
        $editionRepository = new InMemoryEditionRepository();
        $textRepository = new InMemoryTextRepository();
        $text = self::createText();
        $textRepository->add($text);

        $handler = new AssignTextHandler($editionRepository, $textRepository);

        $this->expectException(EditionNotFoundException::class);

        ($handler)(new AssignText(new SlotId(), $text->getId()));
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

    private static function createText(): Text
    {
        return Text::create(
            new TextTitle('Oraison'),
            new TextSource('Auteur'),
            new RichText('<p>Corps.</p>'),
        );
    }
}
