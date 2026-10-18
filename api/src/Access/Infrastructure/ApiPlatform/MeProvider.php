<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Security\SecurityUser;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides the Me resource for the currently authenticated account (F02-T16).
 * Visitors get a 401: the endpoint must not reveal whether an account exists.
 *
 * @implements ProviderInterface<Me>
 */
final class MeProvider implements ProviderInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Me
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof SecurityUser) {
            throw new UnauthenticatedException();
        }

        $account = $this->accountRepository->findById(new AccountId($user->getId()));

        if (null === $account) {
            throw new UnauthenticatedException();
        }

        return new Me($account->getEmail()->value, $user->isOrganizer());
    }
}
