<?php

declare(strict_types=1);

namespace App\Access\Domain\Entity;

use App\Access\Domain\ValueObject\DevicePassId;
use Doctrine\ORM\Mapping as ORM;

/**
 * Device pass aggregate root (F10, RG-IN-6).
 *
 * Granted to the device that made an inscription: it gives the read-only
 * « inscrit » view, never the right to act on behalf of the priant. Only the
 * SHA-256 hash of the cookie token is stored, never the token itself.
 *
 * The pass only lists the inscriptions it covers: the rights are recomputed
 * on each request from those inscriptions (specs-techniques §6.4), so a
 * cancelled inscription gives nothing anymore. The inscription ids are plain
 * RFC 4122 strings: Access must not reference Chain value objects.
 *
 * @see specs-techniques.md §4.3, §6.4
 */
#[ORM\Entity]
#[ORM\Table(name: 'device_pass')]
#[ORM\UniqueConstraint(name: 'device_pass_token_hash_unique', columns: ['token_hash'])]
class DevicePass
{
    #[ORM\Id]
    #[ORM\Embedded(columnPrefix: false)]
    private DevicePassId $id;

    #[ORM\Column(name: 'token_hash', length: 64)]
    private string $tokenHash;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'inscription_ids', type: 'json')]
    private array $inscriptionIds;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'last_seen_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $lastSeenAt;

    /**
     * @param list<string> $inscriptionIds
     */
    public function __construct(
        #[\SensitiveParameter]
        string $token,
        array $inscriptionIds,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $now,
    ) {
        $this->id = new DevicePassId();
        $this->tokenHash = self::hashToken($token);
        $this->inscriptionIds = array_values(array_unique($inscriptionIds));
        $this->expiresAt = $expiresAt;
        $this->lastSeenAt = $now;
    }

    public static function hashToken(#[\SensitiveParameter] string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Adds the inscriptions made from the device. The pass lasts until the
     * latest of its editions has ended (+ 7 days): its expiry never moves
     * forward.
     *
     * @param list<string> $inscriptionIds
     */
    public function cover(array $inscriptionIds, \DateTimeImmutable $expiresAt): void
    {
        $this->inscriptionIds = array_values(array_unique([...$this->inscriptionIds, ...$inscriptionIds]));
        $this->expiresAt = max($this->expiresAt, $expiresAt);
    }

    public function seenAt(\DateTimeImmutable $now): void
    {
        $this->lastSeenAt = $now;
    }

    public function isExpiredAt(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function getId(): DevicePassId
    {
        return $this->id;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    /**
     * @return list<string>
     */
    public function getInscriptionIds(): array
    {
        return $this->inscriptionIds;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }
}
