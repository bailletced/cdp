<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\InMemory\Query;

use App\Chain\Application\Query\AdminInscriptionViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\InMemory\Query\InMemoryAdminInscriptionViewFinder;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryPriantRepository;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class InMemoryAdminInscriptionViewFinderTest extends TestCase
{
    public function testItListsTheInscriptionsOfAnEditionWithNamesAndEmails(): void
    {
        [$finder, $edition, $martin, $marie, $grandMa] = $this->finderWithFixtures();

        $views = $finder->findByEdition($edition->getId());

        self::assertCount(3, $views);
        self::assertSame('Famille Martin', $views[0]->displayName);
        self::assertSame('famille.martin@bailletced.org', $views[0]->email);
        self::assertSame('self', $views[0]->origin);
        self::assertSame(1, $views[0]->slotRank);
        self::assertSame((string) $edition->getSlots()[0]->getId(), $views[0]->slotId);
        // An edition spans two days, so a slot label alone needs its day
        self::assertSame('samedi 28 novembre', $views[0]->dayLabel);
        self::assertSame('Marie Dupont', $views[1]->displayName);
        self::assertSame(2, $views[1]->slotRank);
        self::assertSame('Marie-grand-mère de Thomas', $views[2]->displayName);
        self::assertNull($views[2]->email);
        self::assertSame('organizer', $views[2]->origin);
        self::assertSame(2, $views[2]->slotRank);
        self::assertSame('samedi 28 novembre', $views[2]->dayLabel);

        // The slot label is the Paris wall-clock range of the slot
        self::assertSame('20h00–20h30', $views[0]->slotLabel);
        self::assertSame('2026-11-28T19:00:00+00:00', $views[0]->startsAtUtc);
        self::assertSame('2026-11-28T19:30:00+00:00', $views[0]->endsAtUtc);

        unset($martin, $marie, $grandMa);
    }

    public function testTheSlotFilterNarrowsTheListToOneSlot(): void
    {
        [$finder, $edition] = $this->finderWithFixtures();
        $secondSlotId = new SlotId((string) $edition->getSlots()[1]->getId());

        $views = $finder->findByEdition($edition->getId(), $secondSlotId);

        self::assertCount(2, $views);
        self::assertSame(2, $views[0]->slotRank);
        self::assertSame(2, $views[1]->slotRank);
    }

    public function testAnUnknownEditionHasNoInscriptions(): void
    {
        [$finder] = $this->finderWithFixtures();

        self::assertSame([], $finder->findByEdition(Edition::create(
            new EditionTitle('Autre édition'),
            new Presentation(''),
            new \DateTimeImmutable('2026-12-26 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-12-19 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        )->getId()));
    }

    /**
     * @return array{AdminInscriptionViewFinder, Edition, Priant, Priant, Priant}
     */
    private function finderWithFixtures(): array
    {
        $editions = new InMemoryEditionRepository();
        $priants = new InMemoryPriantRepository();
        $inscriptions = new InMemoryInscriptionRepository();

        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-21 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $editions->add($edition);

        $martin = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $marie = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $grandMa = new Priant(null, new DisplayName('Marie-grand-mère de Thomas'));
        $priants->add($martin);
        $priants->add($marie);
        $priants->add($grandMa);

        $slots = $edition->getSlots();
        $inscriptions->add(new Inscription($edition->getId(), $slots[0]->getId(), $martin->getId()));
        $inscriptions->add(new Inscription($edition->getId(), $slots[1]->getId(), $marie->getId()));
        $inscriptions->add(new Inscription(
            $edition->getId(),
            $slots[1]->getId(),
            $grandMa->getId(),
            Inscription::ORIGIN_ORGANIZER,
        ));

        $finder = new InMemoryAdminInscriptionViewFinder($editions, $priants, $inscriptions);

        return [$finder, $edition, $martin, $marie, $grandMa];
    }
}
