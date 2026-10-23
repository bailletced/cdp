<?php

declare(strict_types=1);

namespace App\Chain\Domain\Entity;

use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Domain\ValueObject\SlotId;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Inscription aggregate root (Participation).
 *
 * One inscription per (slot, priant) — RG-IN-3, guaranteed up to the database
 * by the unique index inscription_slot_priant_unique. An inscription always
 * counts for exactly one (RG-PL-1): there is no headcount.
 *
 * The aggregate references other aggregates by identity only. The optimistic
 * lock (version) is set on Edition and Inscription (specs-techniques §4.3).
 *
 * @see specs-fonctionnelles.md §5.4, RG-IN-3, RG-PL-1
 * @see specs-techniques.md §4.3, §4.5
 */
#[ORM\Entity]
#[ORM\Table(name: 'inscription')]
#[ORM\UniqueConstraint(name: 'inscription_slot_priant_unique', columns: ['slot_id', 'priant_id'])]
class Inscription
{
    public const string ORIGIN_SELF = 'self';

    public const string ORIGIN_ORGANIZER = 'organizer';

    private const array ALLOWED_ORIGINS = [self::ORIGIN_SELF, self::ORIGIN_ORGANIZER];

    #[ORM\Embedded(columnPrefix: false)]
    private InscriptionId $id;

    // References to other aggregates are stored as plain UUID columns and
    // exposed as value objects: the Identity trait marks its column as
    // identifier, which would leak into the primary key if embedded here
    // (same approach as Slot::$textId).
    #[ORM\Column(name: 'edition_id', type: 'uuid')]
    private Uuid $editionId;

    #[ORM\Column(name: 'slot_id', type: 'uuid')]
    private Uuid $slotId;

    #[ORM\Column(name: 'priant_id', type: 'uuid')]
    private Uuid $priantId;

    #[ORM\Column(length: 10)]
    private string $origin;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(
        EditionId $editionId,
        SlotId $slotId,
        PriantId $priantId,
        string $origin = self::ORIGIN_SELF,
    ) {
        if (!\in_array($origin, self::ALLOWED_ORIGINS, true)) {
            throw new InvalidArgumentException(\sprintf('L\'origine d\'inscription doit être "%s" ou "%s".', self::ORIGIN_SELF, self::ORIGIN_ORGANIZER));
        }

        $this->id = new InscriptionId();
        $this->editionId = $editionId->value;
        $this->slotId = $slotId->value;
        $this->priantId = $priantId->value;
        $this->origin = $origin;
    }

    public function getId(): InscriptionId
    {
        return $this->id;
    }

    public function getEditionId(): EditionId
    {
        return new EditionId($this->editionId);
    }

    public function getSlotId(): SlotId
    {
        return new SlotId($this->slotId);
    }

    public function getPriantId(): PriantId
    {
        return new PriantId($this->priantId);
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
