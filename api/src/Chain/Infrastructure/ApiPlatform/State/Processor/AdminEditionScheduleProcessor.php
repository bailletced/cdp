<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Application\UnscheduleEdition;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminEditionResource;
use App\Shared\Application\Command\CommandBusInterface;

/**
 * Processes schedule/unschedule operations on AdminEditionResource (F05-T16).
 *
 * POST /api/admin/editions/{id}/schedule   → ScheduleEdition
 * POST /api/admin/editions/{id}/unschedule → UnscheduleEdition
 *
 * @implements ProcessorInterface<AdminEditionResource, AdminEditionResource>
 */
final readonly class AdminEditionScheduleProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminEditionResource
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new \LogicException('Missing edition identifier in URI variables.');
        }

        $editionId = new EditionId($uriVariables['id']);

        $uriTemplate = $operation instanceof HttpOperation ? ($operation->getUriTemplate() ?? '') : '';

        // EditionNotFoundException propagates: the DomainException mechanism maps it to a 404.
        if (str_contains($uriTemplate, '/schedule')) {
            $this->commandBus->dispatch(new ScheduleEdition($editionId));
        } elseif (str_contains($uriTemplate, '/unschedule')) {
            $this->commandBus->dispatch(new UnscheduleEdition($editionId));
        }

        return new AdminEditionResource(id: (string) $editionId);
    }
}
