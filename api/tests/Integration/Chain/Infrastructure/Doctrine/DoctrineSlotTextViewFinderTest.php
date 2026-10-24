<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Query\SlotTextViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\Doctrine\DoctrineSlotTextViewFinder;
use App\Shared\Domain\Time\ParisCalendar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineSlotTextViewFinderTest extends KernelTestCase
{
    private SlotTextViewFinder $finder;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->finder = new DoctrineSlotTextViewFinder($this->em->getConnection());

        foreach (['inscription', 'priant', 'slot', 'edition', 'text'] as $table) {
            $this->em->getConnection()->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
    }

    public function testTheSlotComesWithItsScheduleAndItsFullText(): void
    {
        $edition = $this->createEdition();
        $text = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Que rien ne te trouble.</p>'), 'Relire lentement.');
        $this->em->persist($text);
        $slot = $edition->getSlots()[2];
        $edition->assignText($slot->getId(), $text->getId());
        $this->em->flush();

        $view = $this->finder->findBySlot($slot->getId());

        self::assertNotNull($view);
        self::assertSame((string) $slot->getId(), $view->slotId);
        self::assertSame((string) $edition->getId(), $view->editionId);
        self::assertSame('24 heures de prière', $view->editionTitle);
        // 19:00 UTC is 20h00 in Paris: slot n° 3 starts at 21h00
        self::assertSame('21h00–21h30', $view->label);
        self::assertSame('samedi 28 novembre', $view->dayLabel);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC')), $view->startsAt);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 20:30:00', new \DateTimeZone('UTC')), $view->endsAt);
        self::assertNotNull($view->text);
        self::assertSame('Que rien ne te trouble', $view->text->title);
        self::assertSame('Thérèse d’Avila', $view->text->source);
        self::assertSame('<p>Que rien ne te trouble.</p>', $view->text->body);
        self::assertSame('Relire lentement.', $view->text->hints);
    }

    public function testASlotWithoutTextHasANullText(): void
    {
        $slot = $this->createEdition()->getSlots()[0];

        $view = $this->finder->findBySlot($slot->getId());

        self::assertNotNull($view);
        self::assertSame('20h00–20h30', $view->label);
        self::assertNull($view->text);
    }

    public function testAnUnknownSlotHasNoView(): void
    {
        $this->createEdition();

        self::assertNull($this->finder->findBySlot(new SlotId()));
    }

    private function createEdition(): Edition
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }
}
