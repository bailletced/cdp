<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\ApiPlatform\Resource\MeResource;
use App\Access\Infrastructure\Symfony\Security\SecurityUser;
use App\Shared\Application\Port\PriantProfileReader;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides the Me resource for the currently authenticated account (F02-T16),
 * enriched with the linked priant profile (F09-T14, RG-IN-7). Visitors get a
 * 401: the endpoint must not reveal whether an account exists.
 *
 * @implements ProviderInterface<MeResource>
 */
final class MeProvider implements ProviderInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly PriantProfileReader $priantProfileReader,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MeResource
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof SecurityUser) {
            throw new UnauthenticatedException();
        }

        $account = $this->accountRepository->findById(new AccountId($user->getId()));

        if (null === $account) {
            throw new UnauthenticatedException();
        }

        $priantId = $account->getPriantId();
        $profile = null === $priantId ? null : $this->priantProfileReader->findProfileByPriantId($priantId);

        return new MeResource(
            email: $account->getEmail()->value,
            isOrganizer: $user->isOrganizer(),
            displayName: $profile?->displayName,
            consentGiven: $profile->consentGiven ?? false,
        );
    }
}
