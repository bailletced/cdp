<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\Doctrine\DoctrineMySpaceViewFinder;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

/**
 * F14-T01, US-14.1: the priant's name and its slots of an edition, with
 * their temporal state and the actions still possible (RG-EP-1: cancel
 * before the slot starts).
 */
final class DoctrineMySpaceViewFinderTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private Edition $edition;

    private Priant $thomas;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        foreach (['inscription', 'priant', 'slot', 'edition', 'text'] as $table) {
            $this->em->getConnection()->executeStatement(\sprintf('DELETE FROM %s', $table));
        }

        // Starts on 2026-11-28 at 20h00 in Paris (19:00 UTC)
        $this->edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->em->persist($this->edition);
        $this->thomas = new Priant(new Email('thomas@bailletced.org'), new DisplayName('Famille Martin'));
        $this->em->persist($this->thomas);
        $this->em->flush();
    }

    public function testMySlotsComeInChronologicalOrderWithTheirTextAndMyName(): void
    {
        $text = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Texte.</p>'));
        $this->em->persist($text);
        $this->edition->assignText($this->edition->getSlots()[4]->getId(), $text->getId());
        $later = $this->register($this->thomas, 4);
        $earlier = $this->register($this->thomas, 1);
        $jean = new Priant(new Email('jean@bailletced.org'), new DisplayName('Jean'));
        $this->em->persist($jean);
        $this->register($jean, 2);

        $view = $this->finderAt('2026-11-20 10:00:00')->find($this->thomas->getId(), $this->edition->getId());

        self::assertNotNull($view);
        self::assertSame('Famille Martin', $view->displayName);
        self::assertSame((string) $this->edition->getId(), $view->editionId);
        self::assertSame('24 heures de prière', $view->editionTitle);
        self::assertCount(2, $view->inscriptions);
        [$first, $second] = $view->inscriptions;
        self::assertSame((string) $earlier->getId(), $first->inscriptionId);
        self::assertSame((string) $this->edition->getSlots()[1]->getId(), $first->slotId);
        self::assertSame('20h30–21h00', $first->label);
        self::assertSame('samedi 28 novembre', $first->dayLabel);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 19:30:00', new \DateTimeZone('UTC')), $first->startsAt);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC')), $first->endsAt);
        self::assertNull($first->textTitle);
        self::assertSame((string) $later->getId(), $second->inscriptionId);
        self::assertSame('Que rien ne te trouble', $second->textTitle);
        self::assertSame('Thérèse d’Avila', $second->textSource);
    }

    public function testBeforeTheSlotItCanBeCancelled(): void
    {
        $this->register($this->thomas, 1);

        $mine = $this->finderAt('2026-11-28 19:29:59')->find($this->thomas->getId(), $this->edition->getId())?->inscriptions[0];

        self::assertSame('future', $mine?->state);
        self::assertSame(['cancel'], $mine->actions);
    }

    public function testOnceTheSlotHasStartedItCanNoLongerBeCancelled(): void
    {
        $this->register($this->thomas, 1);

        $running = $this->finderAt('2026-11-28 19:30:00')->find($this->thomas->getId(), $this->edition->getId())?->inscriptions[0];
        $over = $this->finderAt('2026-11-28 20:00:00')->find($this->thomas->getId(), $this->edition->getId())?->inscriptions[0];

        self::assertSame('current', $running?->state);
        self::assertSame([], $running->actions);
        self::assertSame('past', $over?->state);
        self::assertSame([], $over->actions);
    }

    public function testWithoutInscriptionMySpaceStillCarriesMyName(): void
    {
        $view = $this->finderAt('2026-11-20 10:00:00')->find($this->thomas->getId(), $this->edition->getId());

        self::assertSame('Famille Martin', $view?->displayName);
        self::assertSame([], $view->inscriptions);
    }

    public function testAnUnknownPriantHasNoSpace(): void
    {
        self::assertNull($this->finderAt('2026-11-20 10:00:00')->find(new PriantId(), $this->edition->getId()));
    }

    private function finderAt(string $nowUtc): DoctrineMySpaceViewFinder
    {
        return new DoctrineMySpaceViewFinder(
            $this->em->getConnection(),
            new MockClock(new \DateTimeImmutable($nowUtc, new \DateTimeZone('UTC'))),
        );
    }

    private function register(Priant $priant, int $slotIndex): Inscription
    {
        $inscription = new Inscription($this->edition->getId(), $this->edition->getSlots()[$slotIndex]->getId(), $priant->getId());
        $this->em->persist($inscription);
        $this->em->flush();

        return $inscription;
    }
}
