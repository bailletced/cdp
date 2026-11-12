<?php

declare(strict_types=1);

namespace App\Chain\Domain\Entity;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotTextsSentId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * One delivery of the slot texts email to a priant for an edition (F24).
 *
 * The presence of the row is the fact the command asks about: running the
 * command again sends nothing to the priants who already have theirs. One
 * row per (edition, priant) — guaranteed up to the database by the unique
 * index slot_texts_sent_edition_priant_unique. The instant is stored in UTC.
 */
#[ORM\Entity]
#[ORM\Table(name: 'slot_texts_sent')]
#[ORM\UniqueConstraint(name: 'slot_texts_sent_edition_priant_unique', columns: ['edition_id', 'priant_id'])]
class SlotTextsSent
{
    #[ORM\Embedded(columnPrefix: false)]
    private SlotTextsSentId $id;

    #[ORM\Column(name: 'edition_id', type: 'uuid')]
    private Uuid $editionId;

    #[ORM\Column(name: 'priant_id', type: 'uuid')]
    private Uuid $priantId;

    #[ORM\Column(name: 'sent_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    private function __construct(PriantId $priantId, EditionId $editionId, \DateTimeImmutable $sentAt)
    {
        $this->id = new SlotTextsSentId();
        $this->priantId = $priantId->value;
        $this->editionId = $editionId->value;
        $utc = new \DateTimeZone('UTC');
        $this->sentAt = $sentAt->setTimezone($utc);
    }

    /**
     * Record that the priant received the slot texts email of the edition.
     */
    public static function record(PriantId $priantId, EditionId $editionId, \DateTimeImmutable $at): self
    {
        return new self($priantId, $editionId, $at);
    }

    public function getId(): SlotTextsSentId
    {
        return $this->id;
    }

    public function getEditionId(): EditionId
    {
        return new EditionId($this->editionId);
    }

    public function getPriantId(): PriantId
    {
        return new PriantId($this->priantId);
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }
}
