<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Symfony\Security;

use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Domain\Security\Viewer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Builds the Viewer from the security token only (F02-T12).
 * The device pass cookie (coveredInscriptionIds) is layered on top in F10.
 */
final class SymfonyCurrentViewer implements CurrentViewer
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function current(): Viewer
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof SecurityUser) {
            return Viewer::visitor();
        }

        $account = $this->accountRepository->findById(new AccountId($user->getId()));

        return new Viewer(
            $account?->getPriantId(),
            true,
            [],
            $user->isOrganizer(),
        );
    }
}
