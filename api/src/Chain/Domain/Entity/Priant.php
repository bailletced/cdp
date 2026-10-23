<?php

declare(strict_types=1);

namespace App\Chain\Domain\Entity;

use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;

/**
 * Priant aggregate root (Participation).
 *
 * One email = one priant = one display name (RG-IN-2). A priant may have no
 * email at all: those are created by an organizer (F16). The display name is
 * carried by the priant, never by the inscription; only rename() (priant
 * himself, F14, or an organizer, F16) changes it — a registration form never
 * overwrites an existing name.
 *
 * @see specs-fonctionnelles.md §5.4, RG-IN-2, RG-IN-5, §5.10
 * @see specs-techniques.md §4.3
 */
#[ORM\Entity]
#[ORM\Table(name: 'priant')]
class Priant
{
    #[ORM\Embedded(columnPrefix: false)]
    private PriantId $id;

    // A priant registered by an organizer (F16) has no email: the column is
    // nullable and the Email value object is enforced at the constructor
    // boundary (ORM 3.7 has no nullable embeddables).
    #[ORM\Column(name: 'email', length: 254, nullable: true)]
    private ?string $email = null;

    #[ORM\Embedded(columnPrefix: false)]
    private DisplayName $displayName;

    #[ORM\Column(name: 'consent_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $consentAt = null;

    #[ORM\Column(name: 'unsubscribed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $unsubscribedAt = null;

    #[ORM\Column(name: 'erased_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $erasedAt = null;

    public function __construct(?Email $email, DisplayName $displayName)
    {
        $this->id = new PriantId();
        $this->email = $email?->value;
        $this->displayName = $displayName;
    }

    public function getId(): PriantId
    {
        return $this->id;
    }

    public function getEmail(): ?Email
    {
        return null === $this->email ? null : new Email($this->email);
    }

    public function getDisplayName(): DisplayName
    {
        return $this->displayName;
    }

    public function getConsentAt(): ?\DateTimeImmutable
    {
        return $this->consentAt;
    }

    public function getUnsubscribedAt(): ?\DateTimeImmutable
    {
        return $this->unsubscribedAt;
    }

    public function getErasedAt(): ?\DateTimeImmutable
    {
        return $this->erasedAt;
    }

    /**
     * Change the display name (F14 in session, F16 by an organizer).
     */
    public function rename(DisplayName $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * Record the consent (RG-IN-5, §5.10). The FIRST consent date is kept and
     * never overwritten: consenting again does not move the date.
     */
    public function giveConsent(\DateTimeImmutable $at): void
    {
        $this->consentAt ??= $at;
    }
}
