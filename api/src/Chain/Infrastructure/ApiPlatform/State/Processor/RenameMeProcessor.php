<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\RenamePriant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Infrastructure\ApiPlatform\Payload\RenameMePayload;
use App\Chain\Infrastructure\ApiPlatform\Resource\MyProfileResource;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Processes PATCH /api/me (F14-T04): renames the priant of the session. The
 * name is re-checked here (a blank name passes the length bounds), then
 * RenamePriant is dispatched.
 *
 * @implements ProcessorInterface<RenameMePayload, MyProfileResource>
 */
final readonly class RenameMeProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CurrentViewer $currentViewer,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MyProfileResource
    {
        $viewer = $this->currentViewer->current();

        if (!$viewer->verified) {
            throw new UnauthenticatedException();
        }

        if (null === $viewer->priantId) {
            throw new AccessDeniedException();
        }

        try {
            $displayName = new DisplayName($data->displayName ?? '');
        } catch (\InvalidArgumentException $e) {
            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation($e->getMessage(), null, [], null, 'displayName', $data->displayName)]));
        }

        $this->commandBus->dispatch(new RenamePriant(new PriantId($viewer->priantId), $displayName));

        return new MyProfileResource(displayName: $displayName->value);
    }
}
