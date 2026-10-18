<?php

declare(strict_types=1);

namespace App\Access\Domain\Entity;

use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;

/**
 * Account aggregate root.
 *
 * @phpstan-type AccountData array{
 *   id?: AccountId|null,
 *   email: Email,
 *   credentialsVersion: int,
 *   priantId: string|null
 * }
 */
#[ORM\Entity]
#[ORM\Table(name: 'account')]
class Account
{
    #[ORM\Id]
    #[ORM\Embedded(columnPrefix: false)]
    private AccountId $id;

    #[ORM\Embedded(columnPrefix: false)]
    private Email $email;

    #[ORM\Column(name: 'credentials_version', type: 'integer')]
    private int $credentialsVersion;

    #[ORM\Column(name: 'priant_id', type: 'uuid', nullable: true)]
    private ?string $priantId = null;

    public function __construct(
        AccountId $id,
        Email $email,
        int $credentialsVersion = 1,
        ?string $priantId = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->credentialsVersion = $credentialsVersion;
        $this->priantId = $priantId;
    }

    public function getId(): AccountId
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getCredentialsVersion(): int
    {
        return $this->credentialsVersion;
    }

    public function getPriantId(): ?string
    {
        return $this->priantId;
    }

    /**
     * Increment credentials version (e.g., after password change or login link regeneration).
     */
    public function incrementCredentialsVersion(): void
    {
        ++$this->credentialsVersion;
    }

    public function updatePriantId(?string $priantId): void
    {
        $this->priantId = $priantId;
    }
}
