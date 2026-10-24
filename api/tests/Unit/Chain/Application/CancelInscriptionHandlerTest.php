<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\CancelInscription;
use App\Chain\Application\CancelInscriptionHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Event\InscriptionCancelled;
use App\Chain\Domain\Exception\InscriptionNotFoundException;
use App\Chain\Domain\Exception\SlotAlreadyStartedException;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Shared\Domain\Time\ParisCalendar;
use App\Tests\Unit\Shared\Application\Event\Fixture\EventBusSpy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * CancelInscription (F14-T05, US-14.3, RG-EP-1): free until the slot
 * starts; no e-mail, no alert to the organizers — only the event that
 * refreshes the planning.
 */
final class CancelInscriptionHandlerTest extends TestCase
{
    private InMemoryEditionRepository $editionRepository;

    private InMemoryInscriptionRepository $inscriptionRepository;

    private EventBusSpy $eventBus;

    private Inscription $inscription;

    protected function setUp(): void
    {
        $this->editionRepository = new InMemoryEditionRepository();
        $this->inscriptionRepository = new InMemoryInscriptionRepository();
        $this->eventBus = new EventBusSpy();

        // Slot n° 2 runs from 19:30 to 20:00 UTC on 2026-11-28
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $this->editionRepository->add($edition);
        $this->inscription = new Inscription($edition->getId(), $edition->getSlots()[1]->getId(), new PriantId());
        $this->inscriptionRepository->add($this->inscription);
    }

    public function testBeforeTheSlotTheInscriptionIsCancelledSilently(): void
    {
        $this->handlerAt('2026-11-28 19:29:59')(new CancelInscription($this->inscription->getId()));

        self::assertNull($this->inscriptionRepository->findById($this->inscription->getId()));
        // Only the planning refresh: no e-mail, no alert (RG-EP-1, §5.7)
        self::assertEquals([new InscriptionCancelled(
            $this->inscription->getId(),
            $this->inscription->getPriantId(),
            $this->inscription->getEditionId(),
            $this->inscription->getSlotId(),
        )], $this->eventBus->dispatched);
    }

    public function testOnceTheSlotHasStartedItCanNoLongerBeCancelled(): void
    {
        try {
            $this->handlerAt('2026-11-28 19:30:00')(new CancelInscription($this->inscription->getId()));
            self::fail('A started slot cannot be cancelled.');
        } catch (SlotAlreadyStartedException $e) {
            self::assertSame('Ce créneau a déjà commencé : la désinscription n’est plus possible.', $e->getMessage());
        }

        self::assertNotNull($this->inscriptionRepository->findById($this->inscription->getId()));
        self::assertSame([], $this->eventBus->dispatched);
    }

    public function testAnUnknownInscriptionCannotBeCancelled(): void
    {
        $this->expectException(InscriptionNotFoundException::class);

        $this->handlerAt('2026-11-28 10:00:00')(new CancelInscription(new InscriptionId()));
    }

    private function handlerAt(string $nowUtc): CancelInscriptionHandler
    {
        return new CancelInscriptionHandler(
            $this->inscriptionRepository,
            $this->editionRepository,
            $this->eventBus,
            new MockClock(new \DateTimeImmutable($nowUtc, new \DateTimeZone('UTC'))),
        );
    }
}
