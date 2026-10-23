<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\RegisterInscriptions;
use App\Chain\Application\RegisterInscriptionsHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\Event\InscriptionsRegistered;
use App\Chain\Domain\Exception\AlreadyRegisteredException;
use App\Chain\Domain\Exception\EditionClosedForInscriptionsException;
use App\Chain\Domain\Exception\SlotAlreadyPastException;
use App\Chain\Domain\Exception\SlotNotFoundException;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryPriantRepository;
use App\Shared\Domain\Event\Integration\PriantRegistered;
use App\Shared\Domain\Time\ParisCalendar;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Unit\Shared\Application\Event\Fixture\EventBusSpy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class RegisterInscriptionsHandlerTest extends TestCase
{
    private const string NOW_UTC = '2026-11-01 10:00:00';

    private InMemoryEditionRepository $editionRepository;

    private InMemoryPriantRepository $priantRepository;

    private InMemoryInscriptionRepository $inscriptionRepository;

    private EventBusSpy $eventBus;

    private RegisterInscriptionsHandler $handler;

    protected function setUp(): void
    {
        $this->editionRepository = new InMemoryEditionRepository();
        $this->priantRepository = new InMemoryPriantRepository();
        $this->inscriptionRepository = new InMemoryInscriptionRepository();
        $this->eventBus = new EventBusSpy();

        $this->handler = new RegisterInscriptionsHandler(
            $this->editionRepository,
            $this->priantRepository,
            $this->inscriptionRepository,
            $this->eventBus,
            self::clockAt(self::NOW_UTC),
        );
    }

    public function testItRegistersANewPriantOnSeveralSlots(): void
    {
        $edition = $this->createEdition();
        $slot1 = $edition->getSlots()[0];
        $slot2 = $edition->getSlots()[1];

        $result = ($this->handler)(new RegisterInscriptions(
            [$slot1->getId(), $slot2->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));

        $priant = $this->priantRepository->findByEmail(new Email('famille.martin@bailletced.org'));
        self::assertNotNull($priant);
        self::assertEquals($result->priantId, $priant->getId());
        self::assertSame('Famille Martin', $result->displayName->value);
        self::assertSame('Famille Martin', $priant->getDisplayName()->value);
        self::assertEquals(new \DateTimeImmutable(self::NOW_UTC, new \DateTimeZone('UTC')), $priant->getConsentAt());

        self::assertCount(2, $result->inscriptions);
        self::assertSame(2, $this->inscriptionRepository->countByEdition($edition->getId()));

        self::assertCount(2, $this->eventBus->dispatched);
        $registered = $this->eventBus->dispatched[0];
        self::assertInstanceOf(InscriptionsRegistered::class, $registered);
        self::assertEquals($priant->getId(), $registered->priantId);
        self::assertEquals($edition->getId(), $registered->editionId);
        self::assertCount(2, $registered->slotIds);

        $priantRegistered = $this->eventBus->dispatched[1];
        self::assertInstanceOf(PriantRegistered::class, $priantRegistered);
        self::assertSame($priant->getId()->value->toRfc4122(), $priantRegistered->priantId);
        self::assertEquals(new Email('famille.martin@bailletced.org'), $priantRegistered->email);
    }

    public function testItNeverOverwritesAnExistingDisplayName(): void
    {
        $edition = $this->createEdition();
        $existing = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $this->priantRepository->add($existing);

        $result = ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId()],
            new DisplayName('Thomas Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: false,
        ));

        // RG-IN-2: the name carried by the priant wins over the form input.
        self::assertSame('Famille Martin', $result->displayName->value);
        $priant = $this->priantRepository->findByEmail(new Email('famille.martin@bailletced.org'));
        self::assertNotNull($priant);
        self::assertSame('Famille Martin', $priant->getDisplayName()->value);
        self::assertEquals($existing->getId(), $result->priantId);

        // No PriantRegistered for an already-known priant.
        self::assertCount(1, $this->eventBus->dispatched);
        self::assertInstanceOf(InscriptionsRegistered::class, $this->eventBus->dispatched[0]);
    }

    public function testItKeepsTheFirstConsentDate(): void
    {
        $edition = $this->createEdition();
        $existing = new Priant(new Email('famille.martin@bailletced.org'), new DisplayName('Famille Martin'));
        $firstConsent = new \DateTimeImmutable('2026-10-15 08:00:00', new \DateTimeZone('UTC'));
        $existing->giveConsent($firstConsent);
        $this->priantRepository->add($existing);

        ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));

        // RG-IN-5: the first consent date is kept, never overwritten.
        self::assertEquals($firstConsent, $existing->getConsentAt());
    }

    public function testItRegistersAPriantWithoutEmailAsANewPriantEachTime(): void
    {
        $edition = $this->createEdition();

        $first = ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId()],
            new DisplayName('Famille Dupont'),
            email: null,
            consent: false,
        ));
        $second = ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[1]->getId()],
            new DisplayName('Famille Dupont'),
            email: null,
            consent: false,
        ));

        // A null email never matches: two distinct priants (organizer-created
        // priants have no email, F16).
        self::assertNotEquals($first->priantId, $second->priantId);
        self::assertCount(4, $this->eventBus->dispatched);
        $priantRegistered = $this->eventBus->dispatched[1];
        self::assertInstanceOf(PriantRegistered::class, $priantRegistered);
        self::assertNull($priantRegistered->email);
    }

    public function testItRejectsAnEmptySlotList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->handler)(new RegisterInscriptions(
            [],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    public function testItRejectsAnUnknownSlot(): void
    {
        $this->createEdition();

        $this->expectException(SlotNotFoundException::class);

        ($this->handler)(new RegisterInscriptions(
            [new SlotId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    public function testItRejectsASlotFromAnotherEdition(): void
    {
        $edition = $this->createEdition();
        $otherEdition = $this->createEdition();

        $this->expectException(SlotNotFoundException::class);

        // The first slot selects the edition: a slot of another edition is rejected.
        ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId(), $otherEdition->getSlots()[0]->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    public function testItAcceptsTheCurrentSlotWhileTheEditionIsRunning(): void
    {
        // Edition running since 09:00 UTC: slot 1 (09:00-09:30) and slot 2
        // (09:30-10:00) are past, slot 3 (10:00-10:30) is the current one.
        $edition = $this->createEdition(startsAtUtc: '2026-11-01 09:00:00');

        $result = ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[2]->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));

        self::assertCount(1, $result->inscriptions);
    }

    public function testItRejectsAPastSlot(): void
    {
        $edition = $this->createEdition(startsAtUtc: '2026-11-01 09:00:00');

        $this->expectException(SlotAlreadyPastException::class);

        ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    public function testItRejectsARegistrationWhenTheEditionIsNotOpenYet(): void
    {
        $edition = $this->createEdition(startsAtUtc: '2026-11-08 20:00:00', opensAtUtc: '2026-11-07 00:00:00');

        $this->expectException(EditionClosedForInscriptionsException::class);

        ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    public function testItRejectsARegistrationOnAFinishedEdition(): void
    {
        // The edition started 25 hours ago: it is over.
        $edition = $this->createEdition(startsAtUtc: '2026-10-31 09:00:00', opensAtUtc: '2026-10-30 08:00:00');

        $this->expectException(EditionClosedForInscriptionsException::class);

        ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    public function testItRejectsARegistrationOnADraftEdition(): void
    {
        $edition = $this->createEdition(schedule: false);

        $this->expectException(EditionClosedForInscriptionsException::class);

        ($this->handler)(new RegisterInscriptions(
            [$edition->getSlots()[0]->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    public function testItRejectsADuplicateRegistrationOnTheSameSlot(): void
    {
        $edition = $this->createEdition();
        $slot = $edition->getSlots()[0];

        ($this->handler)(new RegisterInscriptions(
            [$slot->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));

        // RG-IN-3: one inscription per (slot, priant).
        $this->expectException(AlreadyRegisteredException::class);

        ($this->handler)(new RegisterInscriptions(
            [$slot->getId()],
            new DisplayName('Famille Martin'),
            new Email('famille.martin@bailletced.org'),
            consent: true,
        ));
    }

    private function createEdition(
        string $startsAtUtc = '2026-11-28 20:00:00',
        string $opensAtUtc = '2026-10-01 00:00:00',
        bool $schedule = true,
    ): Edition {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable($startsAtUtc, new \DateTimeZone('UTC')),
            new \DateTimeImmutable($opensAtUtc, new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        if ($schedule) {
            $edition->schedule(new \DateTimeImmutable(self::NOW_UTC, new \DateTimeZone('UTC')));
        }

        $this->editionRepository->add($edition);

        return $edition;
    }

    private static function clockAt(string $utc): ClockInterface
    {
        return new class ($utc) implements ClockInterface {
            public function __construct(
                private readonly string $utc,
            ) {
            }

            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable($this->utc, new \DateTimeZone('UTC'));
            }
        };
    }
}
