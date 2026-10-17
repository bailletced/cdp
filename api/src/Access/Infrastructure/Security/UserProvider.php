<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use App\Access\Domain\Account;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * User provider that loads SecurityUser from Account by UUID.
 * Roles are recalculated on each refresh based on the current state.
 */
final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private OrganizerRepositoryInterface $organizerRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $uuid = Uuid::fromString($identifier);
        } catch (\InvalidArgumentException) {
            throw new UserNotFoundException(\sprintf('User with identifier "%s" not found.', $identifier));
        }

        $accountId = new AccountId($uuid);
        $account = $this->accountRepository->findById($accountId);

        if ($account === null) {
            throw new UserNotFoundException(\sprintf('User with identifier "%s" not found.', $identifier));
        }

        return $this->createSecurityUser($account);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        // For compatibility, but we use UUID as identifier
        return $this->loadUserByIdentifier($username);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $accountId = new AccountId($user->getId());
        $account = $this->accountRepository->findById($accountId);

        if ($account === null) {
            throw new UserNotFoundException(\sprintf('User with identifier "%s" not found.', $user->getId()->toRfc4122()));
        }

        return $this->createSecurityUser($account);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || \is_subclass_of($class, SecurityUser::class);
    }

    /**
     * Create a SecurityUser from an Account, checking organizer status.
     */
    private function createSecurityUser(Account $account): SecurityUser
    {
        $isOrganizer = $this->organizerRepository->isOrganizer($account->getId()->value);

        return new SecurityUser(
            $account->getId()->value,
            $account->getEmail()->value,
            '', // Password will be handled separately if needed
            $isOrganizer,
        );
    }
}
