<?php

declare(strict_types=1);

namespace App\Chain\Domain\Entity;

use App\Chain\Domain\Event\EditionScheduled;
use App\Chain\Domain\Event\EditionUnscheduled;
use App\Chain\Domain\Exception\EditionStartLockedException;
use App\Chain\Domain\Exception\SlotNotFoundException;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Domain\ValueObject\TextId;
use App\Shared\Domain\Time\ParisCalendar;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Edition aggregate root (Chain BC).
 *
 * An edition is a 24-hour prayer chain, sliced into 48 real 30-minute slots.
 * Lifecycle: draft → scheduled → (open → running → finished, computed).
 *
 * @see specs-fonctionnelles.md §5.1, RG-ED-1 to RG-ED-7
 * @see specs-techniques.md §4.3, §4.5, §4.7
 */
#[ORM\Entity]
#[ORM\Table(name: 'edition')]
class Edition
{
    public const string LIFECYCLE_DRAFT = 'draft';

    public const string LIFECYCLE_SCHEDULED = 'scheduled';

    #[ORM\Embedded(columnPrefix: false)]
    private EditionId $id;

    #[ORM\Embedded(columnPrefix: false)]
    private EditionTitle $title;

    #[ORM\Embedded(columnPrefix: false)]
    private Presentation $presentation;

    #[ORM\Column(name: 'starts_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(name: 'opens_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $opensAt;

    #[ORM\Column(name: 'lifecycle', length: 20)]
    private string $lifecycle = self::LIFECYCLE_DRAFT;

    #[ORM\Column(name: 'scheduled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    /**
     * @var Collection<int, Slot>
     */
    #[ORM\OneToMany(targetEntity: Slot::class, mappedBy: 'edition', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['rank' => 'ASC'])]
    private Collection $slots;

    private function __construct(
        EditionTitle $title,
        Presentation $presentation,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $opensAt,
    ) {
        $this->id = new EditionId();
        $this->title = $title;
        $this->presentation = $presentation;
        $this->startsAt = $startsAt;
        $this->opensAt = $opensAt;
        $this->slots = new ArrayCollection();
    }

    public static function create(
        EditionTitle $title,
        Presentation $presentation,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $opensAt,
        ParisCalendar $calendar,
    ): self {
        if ($opensAt > $startsAt) {
            throw new InvalidArgumentException('La date d’ouverture des inscriptions doit être antérieure ou égale au début de l’édition.');
        }

        $edition = new self($title, $presentation, $startsAt, $opensAt);
        $edition->generateSlots($calendar);

        return $edition;
    }

    public function getId(): EditionId
    {
        return $this->id;
    }

    public function getTitle(): EditionTitle
    {
        return $this->title;
    }

    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getOpensAt(): \DateTimeImmutable
    {
        return $this->opensAt;
    }

    public function getLifecycle(): string
    {
        return $this->lifecycle;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return Slot[]
     */
    public function getSlots(): array
    {
        return $this->slots->toArray();
    }

    /**
     * Computed status at a given instant (specs-techniques.md §4.7).
     *
     * Hybrid: lifecycle is stored (draft | scheduled), the rest is computed from dates.
     *
     * @return 'draft'|'scheduled'|'open'|'running'|'finished'
     */
    public function statusAt(\DateTimeImmutable $now): string
    {
        if (self::LIFECYCLE_DRAFT === $this->lifecycle) {
            return 'draft';
        }

        $endsAt = $this->startsAt->modify('+24 hours');

        return match (true) {
            $now < $this->opensAt => 'scheduled',
            $now < $this->startsAt => 'open',
            $now < $endsAt => 'running',
            default => 'finished',
        };
    }

    /**
     * Schedule the edition: it becomes invisible to the public until opensAt.
     *
     * @return list<EditionScheduled> Domain events raised by this transition
     */
    public function schedule(\DateTimeImmutable $at): array
    {
        if (self::LIFECYCLE_SCHEDULED === $this->lifecycle) {
            throw new \LogicException('L’édition est déjà programmée.');
        }

        $this->lifecycle = self::LIFECYCLE_SCHEDULED;
        $this->scheduledAt = $at;

        return [new EditionScheduled($this->id, $at)];
    }

    /**
     * Return the edition to draft.
     *
     * @return list<object>
     */
    public function unschedule(\DateTimeImmutable $at): array
    {
        if (self::LIFECYCLE_DRAFT === $this->lifecycle) {
            throw new \LogicException('L’édition est déjà en brouillon.');
        }

        $this->lifecycle = self::LIFECYCLE_DRAFT;
        $this->scheduledAt = null;

        return [new EditionUnscheduled($this->id, $at)];
    }

    /**
     * Reschedule the edition: change the start time and regenerate the 48 slots.
     *
     * RG-ED-3: the start time is no longer modifiable once the first inscription exists.
     * The $hasInscriptions flag is computed by the application layer from the
     * inscription count (F09-T12).
     *
     * @throws EditionStartLockedException if the edition already has inscriptions (RG-ED-3)
     * @throws InvalidArgumentException    if opensAt is after startsAt
     */
    public function reschedule(
        \DateTimeImmutable $newStartsAt,
        \DateTimeImmutable $newOpensAt,
        ParisCalendar $calendar,
        bool $hasInscriptions,
    ): void {
        if ($hasInscriptions) {
            throw new EditionStartLockedException();
        }

        if ($newOpensAt > $newStartsAt) {
            throw new InvalidArgumentException('La date d’ouverture des inscriptions doit être antérieure ou égale au début de l’édition.');
        }

        $this->startsAt = $newStartsAt;
        $this->opensAt = $newOpensAt;
        $this->generateSlots($calendar);
    }

    /**
     * Update the edition's title and presentation.
     *
     * The aggregate is mutated only through its own methods: application
     * code must never write its state through reflection.
     */
    public function updateDetails(EditionTitle $title, Presentation $presentation): void
    {
        $this->title = $title;
        $this->presentation = $presentation;
    }

    /**
     * Whether one of the slots carries the given text (RG-TX-3).
     */
    public function hasTextAttributed(TextId $textId): bool
    {
        foreach ($this->slots as $slot) {
            if ($slot->getTextId()?->equals($textId->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Number of slots carrying the given text (read model of the admin
     * library, US-06.4).
     */
    public function countTextAttributions(TextId $textId): int
    {
        $count = 0;
        foreach ($this->slots as $slot) {
            if ($slot->getTextId()?->equals($textId->value)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Withdraw the attributions of the given text from every slot (RG-TX-3,
     * draft editions only — the DeleteText command refuses otherwise).
     */
    public function removeTextAttributions(TextId $textId): void
    {
        foreach ($this->slots as $slot) {
            if ($slot->getTextId()?->equals($textId->value)) {
                $slot->setTextId(null);
            }
        }
    }

    /**
     * Assign (or withdraw, with null) a text to a single slot (US-07.1).
     *
     * @throws SlotNotFoundException if the slot does not belong to this edition
     */
    public function assignText(SlotId $slotId, ?TextId $textId): void
    {
        $slot = $this->findSlot($slotId);

        $slot->setTextId($textId?->value);
    }

    /**
     * Distribute an ordered list of texts starting at the given rank (US-07.2).
     *
     * The list is truncated at slot 48: texts beyond the last slot are ignored.
     *
     * @param list<TextId> $textIds
     */
    public function distributeTexts(array $textIds, int $fromRank): void
    {
        $rank = $fromRank;
        foreach ($textIds as $textId) {
            if ($rank > 48) {
                break;
            }

            $slot = $this->findSlotByRank($rank);
            $slot->setTextId($textId->value);
            ++$rank;
        }
    }

    /**
     * Find a slot by its identifier within this edition.
     *
     * @throws SlotNotFoundException
     */
    private function findSlot(SlotId $slotId): Slot
    {
        foreach ($this->slots as $slot) {
            if ($slot->getId()->equals($slotId)) {
                return $slot;
            }
        }

        throw new SlotNotFoundException();
    }

    /**
     * Find a slot by its rank (1-based) within this edition.
     *
     * @throws SlotNotFoundException
     */
    private function findSlotByRank(int $rank): Slot
    {
        foreach ($this->slots as $slot) {
            if ($slot->getRank() === $rank) {
                return $slot;
            }
        }

        throw new SlotNotFoundException();
    }

    /**
     * Generate the 48 slots from the start time using the Paris calendar.
     */
    private function generateSlots(ParisCalendar $calendar): void
    {
        $this->slots->clear();

        $timeSlots = $calendar->slice($this->startsAt);

        foreach ($timeSlots as $index => $timeSlot) {
            $slot = new Slot($index + 1, $timeSlot->startsAt, $timeSlot->endsAt);
            $slot->setEdition($this);
            $this->slots->add($slot);
        }
    }
}
