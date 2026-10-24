<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Chain\Application\CancelInscription;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Infrastructure\ApiPlatform\Resource\InscriptionResource;
use App\Chain\Infrastructure\Symfony\Security\InscriptionVoter;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Processes DELETE /api/inscriptions/{id} (F14-T06). A session is required
 * (a device pass only reads, RG-IN-6), then the voter decides
 * (INSCRIPTION_ACT: the priant of the inscription only), then
 * CancelInscription checks the slot has not started (RG-EP-1, 403).
 *
 * @implements ProcessorInterface<mixed, null>
 */
final readonly class CancelInscriptionProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CurrentViewer $currentViewer,
        private InscriptionRepositoryInterface $inscriptionRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$this->currentViewer->current()->verified) {
            throw new UnauthenticatedException();
        }

        try {
            $inscriptionId = new InscriptionId(\is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : '');
        } catch (\InvalidArgumentException) {
            throw new MissingModelException(InscriptionResource::class);
        }

        $inscription = $this->inscriptionRepository->findById($inscriptionId);

        if (null === $inscription) {
            throw new MissingModelException(InscriptionResource::class);
        }

        if (!$this->authorizationChecker->isGranted(InscriptionVoter::ACT, $inscription)) {
            throw new AccessDeniedException();
        }

        $this->commandBus->dispatch(new CancelInscription($inscriptionId));

        return null;
    }
}
