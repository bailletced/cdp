<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Domain\Entity;

use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Slot;
use App\Chain\Domain\Exception\EditionStartLockedException;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\TextId;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;

final class EditionTest extends TestCase
{
    public function testCreateEditionGenerates48Slots(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        self::assertCount(48, $edition->getSlots());
        self::assertContainsOnlyInstancesOf(Slot::class, $edition->getSlots());
    }

    public function testNewEditionIsInDraftLifecycle(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        self::assertSame('draft', $edition->getLifecycle());
    }

    public function testStatusAtReturnsDraftWhenLifecycleIsDraft(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $now = new \DateTimeImmutable('2026-11-20 12:00:00', new \DateTimeZone('UTC'));

        self::assertSame('draft', $edition->statusAt($now));
    }

    public function testScheduleChangesLifecycleToScheduled(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $at = new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC'));

        $events = $edition->schedule($at);

        self::assertSame('scheduled', $edition->getLifecycle());
        self::assertSame($at, $edition->getScheduledAt());
        self::assertCount(1, $events);
    }

    public function testUnscheduleReturnsToDraft(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC')));
        $at = new \DateTimeImmutable('2026-11-02 10:00:00', new \DateTimeZone('UTC'));

        $events = $edition->unschedule($at);

        self::assertSame('draft', $edition->getLifecycle());
        self::assertNull($edition->getScheduledAt());
        self::assertCount(1, $events);
    }

    public function testScheduleThrowsWhenAlreadyScheduled(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC')));

        $this->expectException(\LogicException::class);

        $edition->schedule(new \DateTimeImmutable('2026-11-02 10:00:00', new \DateTimeZone('UTC')));
    }

    public function testUnscheduleThrowsWhenAlreadyDraft(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        $this->expectException(\LogicException::class);

        $edition->unschedule(new \DateTimeImmutable('2026-11-02 10:00:00', new \DateTimeZone('UTC')));
    }

    public function testStatusAtReturnsScheduledBeforeOpensAt(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC')));
        $now = new \DateTimeImmutable('2026-11-10 12:00:00', new \DateTimeZone('UTC'));

        self::assertSame('scheduled', $edition->statusAt($now));
    }

    public function testStatusAtReturnsOpenBetweenOpensAtAndStartsAt(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC')));
        $now = new \DateTimeImmutable('2026-11-20 12:00:00', new \DateTimeZone('UTC'));

        self::assertSame('open', $edition->statusAt($now));
    }

    public function testStatusAtReturnsRunningBetweenStartsAtAndEndsAt(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC')));
        $now = new \DateTimeImmutable('2026-11-29 00:00:00', new \DateTimeZone('UTC'));

        self::assertSame('running', $edition->statusAt($now));
    }

    public function testStatusAtReturnsFinishedAfterEndsAt(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $edition->schedule(new \DateTimeImmutable('2026-11-01 10:00:00', new \DateTimeZone('UTC')));
        $now = new \DateTimeImmutable('2026-11-29 19:00:00', new \DateTimeZone('UTC'));

        self::assertSame('finished', $edition->statusAt($now));
    }

    public function testRescheduleRegenerates48Slots(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $newStartsAt = new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC'));
        $newOpensAt = new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('UTC'));

        $edition->reschedule($newStartsAt, $newOpensAt, new ParisCalendar(), hasInscriptions: false);

        self::assertSame('2026-11-28 20:00:00', $edition->getStartsAt()->format('Y-m-d H:i:s'));
        self::assertCount(48, $edition->getSlots());
        self::assertSame('2026-11-28 20:00:00', $edition->getSlots()[0]->getStartsAt()->format('Y-m-d H:i:s'));
    }

    public function testRescheduleMovesTheSlotsInPlaceAndKeepsTheirTexts(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $textId = new TextId();
        $edition->distributeTexts([$textId], 1);
        $slotIds = array_map(static fn (Slot $slot): string => (string) $slot->getId(), $edition->getSlots());

        $edition->reschedule(
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
            hasInscriptions: false,
        );

        // Same slots (same ids), moved by one hour, attributions kept (F07)
        self::assertSame($slotIds, array_map(static fn (Slot $slot): string => (string) $slot->getId(), $edition->getSlots()));
        self::assertSame('2026-11-28 20:30:00', $edition->getSlots()[1]->getStartsAt()->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-28 21:00:00', $edition->getSlots()[1]->getEndsAt()->format('Y-m-d H:i:s'));
        self::assertTrue($edition->hasTextAttributed($textId));
    }

    public function testRescheduleAllowsChangingTheOpeningAfterTheFirstInscription(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        // RG-ED-3 locks the start time only: the opening date stays editable.
        $edition->reschedule(
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-14 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
            hasInscriptions: true,
        );

        self::assertSame('2026-11-14 19:00:00', $edition->getOpensAt()->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-28 19:00:00', $edition->getStartsAt()->format('Y-m-d H:i:s'));
    }

    public function testRescheduleThrowsWhenTheEditionHasInscriptions(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        // RG-ED-3: locked after the first inscription.
        $this->expectException(EditionStartLockedException::class);

        $edition->reschedule(
            new \DateTimeImmutable('2026-11-28 20:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 20:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
            hasInscriptions: true,
        );
    }

    public function testHasTextAttributedReturnsTrueWhenASlotCarriesTheText(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $textId = new TextId();

        $edition->getSlots()[0]->setTextId($textId->value);

        self::assertTrue($edition->hasTextAttributed($textId));
    }

    public function testHasTextAttributedReturnsFalseWhenNoSlotCarriesTheText(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        self::assertFalse($edition->hasTextAttributed(new TextId()));
    }

    public function testRemoveTextAttributionsClearsOnlyMatchingSlots(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $textId = new TextId();
        $otherTextId = new TextId();
        $slots = $edition->getSlots();
        $slots[0]->setTextId($textId->value);
        $slots[1]->setTextId($textId->value);
        $slots[2]->setTextId($otherTextId->value);

        $edition->removeTextAttributions($textId);

        self::assertNull($slots[0]->getTextId());
        self::assertNull($slots[1]->getTextId());
        self::assertSame($otherTextId->value, $slots[2]->getTextId());
        self::assertFalse($edition->hasTextAttributed($textId));
        self::assertTrue($edition->hasTextAttributed($otherTextId));
    }

    public function testCountTextAttributionsReturnsTheNumberOfSlotsCarryingTheText(): void
    {
        $edition = Edition::create(
            new EditionTitle('24 heures de prière'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );
        $textId = new TextId();
        $otherTextId = new TextId();
        $slots = $edition->getSlots();

        self::assertSame(0, $edition->countTextAttributions($textId));

        $slots[0]->setTextId($textId->value);
        $slots[2]->setTextId($textId->value);
        $slots[1]->setTextId($otherTextId->value);

        self::assertSame(2, $edition->countTextAttributions($textId));
        self::assertSame(1, $edition->countTextAttributions($otherTextId));
    }

    public function testItUpdatesItsTitleAndPresentation(): void
    {
        $edition = Edition::create(
            new EditionTitle('Ancien titre'),
            new Presentation(''),
            new \DateTimeImmutable('2026-11-28 19:00:00', new \DateTimeZone('UTC')),
            new \DateTimeImmutable('2026-11-13 19:00:00', new \DateTimeZone('UTC')),
            new ParisCalendar(),
        );

        $edition->updateDetails(new EditionTitle('Nouveau titre'), new Presentation('Nouvelle présentation'));

        self::assertSame('Nouveau titre', $edition->getTitle()->value);
        self::assertSame('Nouvelle présentation', $edition->getPresentation()->value);
    }

    public function testAssignTextSetsTextIdOnTheMatchingSlot(): void
    {
        $edition = self::createEdition();
        $slot = $edition->getSlots()[0];
        $textId = new TextId();

        $edition->assignText($slot->getId(), $textId);

        self::assertTrue($slot->getTextId()?->equals($textId->value));
    }

    public function testAssignTextWithNullRemovesTheTextId(): void
    {
        $edition = self::createEdition();
        $slot = $edition->getSlots()[0];
        $slot->setTextId(new TextId()->value);

        $edition->assignText($slot->getId(), null);

        self::assertNull($slot->getTextId());
    }

    public function testAssignTextOnUnknownSlotThrowsSlotNotFound(): void
    {
        $edition = self::createEdition();

        $this->expectException(\App\Chain\Domain\Exception\SlotNotFoundException::class);

        $edition->assignText(new \App\Chain\Domain\ValueObject\SlotId(), new TextId());
    }

    public function testDistributeTextsAssignsTextsSequentiallyFromTheGivenRank(): void
    {
        $edition = self::createEdition();
        $textIds = [
            new TextId(),
            new TextId(),
            new TextId(),
        ];

        $edition->distributeTexts($textIds, 1);

        $slots = $edition->getSlots();
        self::assertTrue($slots[0]->getTextId()?->equals($textIds[0]->value));
        self::assertTrue($slots[1]->getTextId()?->equals($textIds[1]->value));
        self::assertTrue($slots[2]->getTextId()?->equals($textIds[2]->value));
        self::assertNull($slots[3]->getTextId());
    }

    public function testDistributeTextsTruncatesAtSlot48(): void
    {
        $edition = self::createEdition();
        $textIds = [];
        for ($i = 0; $i < 10; ++$i) {
            $textIds[] = new TextId();
        }

        $edition->distributeTexts($textIds, 40);

        $slots = $edition->getSlots();
        // Slots 40 to 48 (ranks 40–48) are filled: 9 slots
        self::assertTrue($slots[39]->getTextId()?->equals($textIds[0]->value));
        self::assertTrue($slots[47]->getTextId()?->equals($textIds[8]->value));
        // The 10th text is truncated (no slot 49)
        self::assertCount(48, $slots);
    }

    public function testDistributeTextsWithEmptyListLeavesSlotsUntouched(): void
    {
        $edition = self::createEdition();

        $edition->distributeTexts([], 1);

        foreach ($edition->getSlots() as $slot) {
            self::assertNull($slot->getTextId());
        }
    }

    public function testDistributeTextsOverwritesExistingAttributions(): void
    {
        $edition = self::createEdition();
        $slots = $edition->getSlots();
        $slots[0]->setTextId(new TextId()->value);

        $newTextId = new TextId();
        $edition->distributeTexts([$newTextId], 1);

        self::assertTrue($slots[0]->getTextId()?->equals($newTextId->value));
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
}
