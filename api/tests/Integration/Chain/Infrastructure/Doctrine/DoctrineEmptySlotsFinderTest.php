<?php

declare(strict_types=1);

namespace App\Tests\Integration\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\EmptySlotView;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\RichText;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Chain\Infrastructure\Doctrine\DoctrineEmptySlotsFinder;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

/**
 * F13-T02, US-13.1: the empty slots still to come of a public edition —
 * all of them before the edition, only the coming ones during it.
 */
final class DoctrineEmptySlotsFinderTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private Edition $edition;

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
        $this->edition->schedule(new \DateTimeImmutable('2026-11-01 00:00:00', new \DateTimeZone('UTC')));
        $this->em->persist($this->edition);
        $this->em->flush();
    }

    public function testBeforeTheEditionEveryEmptySlotIsCountedInChronologicalOrder(): void
    {
        $text = Text::create(new TextTitle('Que rien ne te trouble'), new TextSource('Thérèse d’Avila'), new RichText('<p>Texte.</p>'));
        $this->em->persist($text);
        $this->edition->assignText($this->edition->getSlots()[1]->getId(), $text->getId());
        $this->register(0, 2);

        $slots = $this->finderAt('2026-11-20 10:00:00')->findByEdition($this->edition->getId());

        self::assertCount(46, $slots);
        [$first, $second] = $slots;
        self::assertSame((string) $this->edition->getSlots()[1]->getId(), $first->id);
        self::assertSame(2, $first->rank);
        self::assertSame('20h30–21h00', $first->label);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 19:30:00', new \DateTimeZone('UTC')), $first->startsAt);
        self::assertEquals(new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC')), $first->endsAt);
        self::assertSame('Que rien ne te trouble', $first->textTitle);
        self::assertSame('Thérèse d’Avila', $first->textSource);
        self::assertSame(4, $second->rank);
        self::assertNull($second->textTitle);
    }

    public function testDuringTheEditionOnlyTheComingEmptySlotsAreCounted(): void
    {
        $this->register(10);

        // 21:10 UTC: slots n° 1 to 4 are over, n° 5 (21:00–21:30) is running
        $slots = $this->finderAt('2026-11-28 21:10:00')->findByEdition($this->edition->getId());

        // n° 11 (index 10) is taken
        self::assertSame(
            array_values(array_diff(range(6, 48), [11])),
            array_map(static fn (EmptySlotView $slot): int => $slot->rank, $slots),
        );
    }

    public function testACompleteChainHasNoEmptySlot(): void
    {
        $this->register(...range(0, 47));

        self::assertSame([], $this->finderAt('2026-11-20 10:00:00')->findByEdition($this->edition->getId()));
    }

    public function testADraftOrUnknownEditionHasNoEmptySlotToOffer(): void
    {
        $this->edition->unschedule(new \DateTimeImmutable('2026-11-02 00:00:00', new \DateTimeZone('UTC')));
        $this->em->flush();

        self::assertSame([], $this->finderAt('2026-11-20 10:00:00')->findByEdition($this->edition->getId()));
        self::assertSame([], $this->finderAt('2026-11-20 10:00:00')->findByEdition(new EditionId()));
    }

    private function finderAt(string $nowUtc): DoctrineEmptySlotsFinder
    {
        return new DoctrineEmptySlotsFinder(
            $this->em->getConnection(),
            new MockClock(new \DateTimeImmutable($nowUtc, new \DateTimeZone('UTC'))),
        );
    }

    private function register(int ...$slotIndexes): void
    {
        $priant = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $this->em->persist($priant);
        foreach ($slotIndexes as $index) {
            $this->em->persist(new Inscription($this->edition->getId(), $this->edition->getSlots()[$index]->getId(), $priant->getId()));
        }
        $this->em->flush();
    }
}
