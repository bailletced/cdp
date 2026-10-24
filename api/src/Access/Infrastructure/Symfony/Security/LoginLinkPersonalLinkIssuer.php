<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Symfony\Security;

use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Shared\Application\Dto\PersonalLink;
use App\Shared\Application\Port\PersonalLinkIssuer;
use App\Shared\Domain\ValueObject\Email;
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
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function issue(string $accountId, ?\DateTimeImmutable $editionEndsAt = null): PersonalLink
    {
        $user = $this->userProvider->loadUserByIdentifier($accountId);

        $expiresAt = $this->computeExpiresAt($user->isOrganizer(), $editionEndsAt);
        $lifetime = $expiresAt->getTimestamp() - $this->clock->now()->getTimestamp();

        $details = $this->loginLinkHandler->createLoginLink($user, null, $lifetime);

        return new PersonalLink($details->getUrl(), $details->getExpiresAt());
    }

    public function issueForEmail(Email $email, ?\DateTimeImmutable $editionEndsAt = null): PersonalLink
    {
        $account = $this->accountRepository->findByEmail($email);

        if (null === $account) {
            throw new \RuntimeException('No account is linked to this email yet.');
        }

        return $this->issue($account->getId()->value->toRfc4122(), $editionEndsAt);
    }

    /**
     * Compute the link expiry from the edition context.
     * During an edition the link survives the edition's 7-day grace period;
     * organizers always get the standard 7-day lifetime.
     */
    private function computeExpiresAt(bool $isOrganizer, ?\DateTimeImmutable $editionEndsAt): \DateTimeImmutable
    {
        $now = $this->clock->now();

        if (!$isOrganizer && null !== $editionEndsAt) {
            $editionGraceEnd = $editionEndsAt->modify(self::SEVEN_DAYS);
            if ($now < $editionGraceEnd) {
                return $editionGraceEnd;
            }
        }

        return $now->modify(self::SEVEN_DAYS);
    }
}
