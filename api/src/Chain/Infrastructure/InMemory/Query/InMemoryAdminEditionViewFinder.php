<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\InMemory\Query;

use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Dto\AdminSlotView;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\TextId;
use App\Shared\Domain\Time\PartOfDay;
use App\Shared\Domain\Time\SlotLabel;
use App\Shared\Domain\Time\TimeSlot;
use Psr\Clock\ClockInterface;

/**
 * In-memory implementation of AdminEditionViewFinder for testing.
 */
final readonly class InMemoryAdminEditionViewFinder implements AdminEditionViewFinder
{
    public function __construct(
        private EditionRepositoryInterface $editionRepository,
        private TextRepositoryInterface $textRepository,
        private ClockInterface $clock,
    ) {
    }

    public function findAll(): array
    {
        $editions = $this->editionRepository->findAll();
        $views = [];

        foreach ($editions as $edition) {
            $views[] = $this->toView($edition);
        }

        // Most recent first
        usort($views, static fn (AdminEditionView $a, AdminEditionView $b): int => $b->startsAtUtc <=> $a->startsAtUtc);

        return $views;
    }

    public function findOne(EditionId $id): ?AdminEditionView
    {
        $edition = $this->editionRepository->findById($id);

        if (null === $edition) {
            return null;
        }

        return $this->toView($edition);
    }

    private function toView(Edition $edition): AdminEditionView
    {
        $now = $this->clock->now();
        $status = $edition->statusAt($now);

        // How many slots carry each text, to flag the duplicates (US-07.1)
        $uses = [];
        foreach ($edition->getSlots() as $slot) {
            $textId = $slot->getTextId()?->toRfc4122();
            if (null !== $textId) {
                $uses[$textId] = ($uses[$textId] ?? 0) + 1;
            }
        }

        $slotViews = [];
        $slotsWithoutText = 0;
        foreach ($edition->getSlots() as $slot) {
            $timeSlot = new TimeSlot($slot->getStartsAt(), $slot->getEndsAt());
            $textId = $slot->getTextId()?->toRfc4122();
            $text = null !== $textId ? $this->textRepository->findById(new TextId($textId)) : null;

            if (null === $textId) {
                ++$slotsWithoutText;
            }

            $slotViews[] = new AdminSlotView(
                id: (string) $slot->getId(),
                rank: $slot->getRank(),
                startsAtUtc: $slot->getStartsAt()->format(\DateTimeInterface::ATOM),
                endsAtUtc: $slot->getEndsAt()->format(\DateTimeInterface::ATOM),
                label: SlotLabel::for($timeSlot),
                partOfDay: PartOfDay::forSlot($timeSlot)->value,
                textId: $textId,
                textTitle: $text?->getTitle()->value,
                textSource: $text?->getSource()->value,
                textAlreadyUsed: null !== $textId && $uses[$textId] > 1,
            );
        }

        return new AdminEditionView(
            id: (string) $edition->getId(),
            title: $edition->getTitle()->value,
            presentation: $edition->getPresentation()->value,
            startsAtUtc: $edition->getStartsAt()->format(\DateTimeInterface::ATOM),
            opensAtUtc: $edition->getOpensAt()->format(\DateTimeInterface::ATOM),
            lifecycle: $edition->getLifecycle(),
            status: $status,
            scheduledAtUtc: $edition->getScheduledAt()?->format(\DateTimeInterface::ATOM),
            version: $edition->getVersion(),
            slots: $slotViews,
            slotsWithoutText: $slotsWithoutText,
            duplicatedTextIds: array_keys(array_filter($uses, static fn (int $count): bool => $count > 1)),
        );
    }
}
