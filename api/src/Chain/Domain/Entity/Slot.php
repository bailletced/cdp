<?php

declare(strict_types=1);

namespace App\Chain\Domain\Entity;

use App\Chain\Domain\ValueObject\SlotId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Internal entity of the Edition aggregate.
 *
 * A slot is a real 30-minute time window. It belongs to exactly one edition
 * and cannot exist independently. Slots are generated at creation time and
 * regenerated when the edition start changes (before the first inscription, RG-ED-3).
 */
#[ORM\Entity]
#[ORM\Table(name: 'slot')]
#[ORM\UniqueConstraint(name: 'slot_edition_rank_unique', columns: ['edition_id', 'rank'])]
class Slot
{
    #[ORM\Embedded(columnPrefix: false)]
    private SlotId $id;

    #[ORM\Column(type: 'smallint')]
    private int $rank;

    #[ORM\Column(name: 'starts_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(name: 'ends_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $endsAt;

    #[ORM\Column(name: 'text_id', type: 'uuid', nullable: true)]
    private ?Uuid $textId = null;

    #[ORM\ManyToOne(targetEntity: Edition::class, inversedBy: 'slots')]
    #[ORM\JoinColumn(name: 'edition_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Edition $edition = null;

    public function __construct(int $rank, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt)
    {
        $this->id = new SlotId();
        $this->rank = $rank;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
    }

    public function getId(): SlotId
    {
        return $this->id;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getTextId(): ?Uuid
    {
        return $this->textId;
    }

    public function setTextId(?Uuid $textId): void
    {
        $this->textId = $textId;
    }

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): void
    {
        $this->edition = $edition;
    }
}
