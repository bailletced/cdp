<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Application;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\UpdateEdition;
use App\Chain\Application\UpdateEditionHandler;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\EditionStartLockedException;
use App\Chain\Domain\Exception\EditionVersionConflictException;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryInscriptionRepository;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class UpdateEditionHandlerTest extends TestCase
{
    private InMemoryEditionRepository $repository;

    private InMemoryInscriptionRepository $inscriptionRepository;

    private ParisCalendar $calendar;

    private CreateEditionHandler $createHandler;

    private UpdateEditionHandler $updateHandler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryEditionRepository();
        $this->inscriptionRepository = new InMemoryInscriptionRepository();
        $this->calendar = new ParisCalendar();
        $this->createHandler = new CreateEditionHandler($this->repository, $this->calendar);
        $this->updateHandler = new UpdateEditionHandler($this->repository, $this->inscriptionRepository, $this->calendar);
    }

    public function testItUpdatesAnEdition(): void
    {
        $createResult = ($this->createHandler)(new CreateEdition(
            new EditionTitle('Ancien titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));

        $result = ($this->updateHandler)(new UpdateEdition(
            $createResult->editionId,
            new EditionTitle('Nouveau titre'),
            new Presentation('Nouvelle présentation'),
            new \DateTimeImmutable('2026-11-28 21:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 21:00:00', new \DateTimeZone('Europe/Paris')),
            1,
        ));

        $edition = $this->repository->findById($createResult->editionId);
        self::assertNotNull($edition);
        self::assertSame('Nouveau titre', $edition->getTitle()->value);
        self::assertSame('Nouvelle présentation', $edition->getPresentation()->value);
        // 2026-11-28 21:00 CET = 20:00 UTC
        self::assertSame('2026-11-28 20:00:00', $edition->getStartsAt()->format('Y-m-d H:i:s'));
        self::assertCount(48, $edition->getSlots());
    }

    public function testItThrowsConflictOnVersionMismatch(): void
    {
        $createResult = ($this->createHandler)(new CreateEdition(
            new EditionTitle('Titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));

        $this->expectException(EditionVersionConflictException::class);

        ($this->updateHandler)(new UpdateEdition(
            $createResult->editionId,
            new EditionTitle('Nouveau titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 21:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 21:00:00', new \DateTimeZone('Europe/Paris')),
            999, // wrong version
        ));
    }

    public function testItThrowsWhenEditionIsNotFound(): void
    {
        $this->expectException(EditionNotFoundException::class);

        ($this->updateHandler)(new UpdateEdition(
            new EditionId(),
            new EditionTitle('Nouveau titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 21:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 21:00:00', new \DateTimeZone('Europe/Paris')),
            1,
        ));
    }

    public function testItStillUpdatesTitleAndOpeningAfterTheFirstInscription(): void
    {
        $createResult = ($this->createHandler)(new CreateEdition(
            new EditionTitle('Titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));
        $this->inscriptionRepository->add(new Inscription($createResult->editionId, new SlotId(), new PriantId()));

        // Same start: RG-ED-3 does not apply, the opening date and the title change.
        ($this->updateHandler)(new UpdateEdition(
            $createResult->editionId,
            new EditionTitle('Nouveau titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-14 20:00:00', new \DateTimeZone('Europe/Paris')),
            1,
        ));

        $edition = $this->repository->findById($createResult->editionId);
        self::assertNotNull($edition);
        self::assertSame('Nouveau titre', $edition->getTitle()->value);
        self::assertSame('2026-11-14 19:00:00', $edition->getOpensAt()->format('Y-m-d H:i:s'));
    }

    public function testItLocksTheStartTimeAfterTheFirstInscription(): void
    {
        $createResult = ($this->createHandler)(new CreateEdition(
            new EditionTitle('Titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('Europe/Paris')),
        ));

        // A first inscription exists (F09): the start time is locked (RG-ED-3).
        $this->inscriptionRepository->add(new Inscription($createResult->editionId, new SlotId(), new PriantId()));

        $this->expectException(EditionStartLockedException::class);

        ($this->updateHandler)(new UpdateEdition(
            $createResult->editionId,
            new EditionTitle('Nouveau titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 21:00:00', new \DateTimeZone('Europe/Paris')),
            new \DateTimeImmutable('2026-11-13 21:00:00', new \DateTimeZone('Europe/Paris')),
            1,
        ));
    }
}
