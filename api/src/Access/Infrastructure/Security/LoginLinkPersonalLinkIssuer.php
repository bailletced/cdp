<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use App\Shared\Application\Dto\PersonalLink;
use App\Shared\Application\Port\PersonalLinkIssuer;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * Personal link issuer backed by Symfony's login_link (F02-T11).
 *
 * The lifetime is set per link via createLoginLink($user, null, $lifetime):
 * - during an edition (up to its end + 7 days): the link expires at edition end + 7 days
 * - otherwise (between editions): 7 days
 * - organizer accounts: always 7 days
 */
final class LoginLinkPersonalLinkIssuer implements PersonalLinkIssuer
{
    private const SEVEN_DAYS = '+7 days';

    public function __construct(
        private UserProvider $userProvider,
        private LoginLinkHandlerInterface $loginLinkHandler,
        private ClockInterface $clock,
    ) {
    }

    public function issue(string $accountId, ?\DateTimeImmutable $editionEndsAt = null): PersonalLink
    {
        $user = $this->userProvider->loadUserByIdentifier($accountId);
        if (!$user instanceof SecurityUser) {
            throw new \LogicException(\sprintf('Expected %s, got %s', SecurityUser::class, get_debug_type($user)));
        }

        $expiresAt = $this->computeExpiresAt($user->isOrganizer(), $editionEndsAt);
        $lifetime = $expiresAt->getTimestamp() - $this->clock->now()->getTimestamp();

        $details = $this->loginLinkHandler->createLoginLink($user, null, $lifetime);

        return new PersonalLink($details->getUrl(), $details->getExpiresAt());
    }

    /**
     * Compute the link expiry from the edition context.
     * During an edition the link survives the edition's 7-day grace period;
     * organizers always get the standard 7-day lifetime.
     */
    private function computeExpiresAt(bool $isOrganizer, ?\DateTimeImmutable $editionEndsAt): \DateTimeImmutable
    {
        $now = $this->clock->now();

        if (!$isOrganizer && $editionEndsAt !== null) {
            $editionGraceEnd = $editionEndsAt->modify(self::SEVEN_DAYS);
            if ($now < $editionGraceEnd) {
                return $editionGraceEnd;
            }
        }

        return $now->modify(self::SEVEN_DAYS);
    }
}
