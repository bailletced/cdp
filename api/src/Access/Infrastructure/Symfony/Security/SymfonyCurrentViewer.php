<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Symfony\Security;

use App\Access\Application\PresentDevicePass;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Application\Port\InscriptionDirectory;
use App\Shared\Domain\Security\Viewer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Builds the Viewer from the security token (F02-T12) and the device pass
 * cookie (F10-T04).
 *
 * The rights of a pass are recomputed on each request: an expired pass
 * covers nothing, and only the inscriptions still active are kept.
 */
final readonly class SymfonyCurrentViewer implements CurrentViewer
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AccountRepositoryInterface $accountRepository,
        private RequestStack $requestStack,
        private CommandBusInterface $commandBus,
        private InscriptionDirectory $inscriptionDirectory,
    ) {
    }

    public function current(): Viewer
    {
        $covered = $this->coveredInscriptionIds();
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof SecurityUser) {
            return new Viewer(null, false, $covered, false);
        }

        $account = $this->accountRepository->findById(new AccountId($user->getId()));

        return new Viewer(
            $account?->getPriantId(),
            true,
            $covered,
            $user->isOrganizer(),
        );
    }

    /**
     * @return list<string>
     */
    private function coveredInscriptionIds(): array
    {
        $token = $this->requestStack->getMainRequest()?->cookies->get(DevicePassCookie::NAME);

        if (!\is_string($token) || '' === $token) {
            return [];
        }

        $covered = $this->commandBus->dispatch(new PresentDevicePass($token));

        return $this->inscriptionDirectory->filterActive(
            \is_array($covered) ? array_values(array_filter($covered, \is_string(...))) : [],
        );
    }
}
