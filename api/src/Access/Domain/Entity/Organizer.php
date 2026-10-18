<?php

declare(strict_types=1);

namespace App\Access\Domain\Entity;

use App\Access\Domain\ValueObject\OrganizerId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Organizer aggregate root.
 *
 * Represents a user with organizer privileges.
 */
#[ORM\Entity]
#[ORM\Table(name: 'organizer')]
class Organizer
{
    #[ORM\Id]
    #[ORM\Embedded(columnPrefix: false)]
    private OrganizerId $id;

    #[ORM\Column(name: 'account_id', type: 'uuid')]
    private Uuid $accountId;

    #[ORM\Column(name: 'added_by', type: 'uuid')]
    private Uuid $addedBy;

    #[ORM\Column(name: 'added_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $addedAt;

    public function __construct(
        Uuid $accountId,
        Uuid $addedBy,
        \DateTimeImmutable $addedAt = new \DateTimeImmutable()
    ) {
        $this->id = new OrganizerId();
        $this->accountId = $accountId;
        $this->addedBy = $addedBy;
        $this->addedAt = $addedAt;
    }

    public function getId(): OrganizerId
    {
        return $this->id;
    }

    public function getAccountId(): Uuid
    {
        return $this->accountId;
    }

    public function getAddedBy(): Uuid
    {
        return $this->addedBy;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
