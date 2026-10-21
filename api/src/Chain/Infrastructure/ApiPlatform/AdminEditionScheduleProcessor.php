<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Application\UnscheduleEdition;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\ValueObject\EditionId;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Processes schedule/unschedule operations on AdminEdition (F05-T16).
 *
 * POST /api/admin/editions/{id}/schedule   → ScheduleEdition
 * POST /api/admin/editions/{id}/unschedule → UnscheduleEdition
 *
 * @implements ProcessorInterface<AdminEdition, AdminEdition>
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminEdition
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new \LogicException('Missing edition identifier in URI variables.');
        }

        $editionId = new EditionId($uriVariables['id']);

        $uriTemplate = $operation instanceof HttpOperation ? ($operation->getUriTemplate() ?? '') : '';

        try {
            if (str_contains($uriTemplate, '/schedule')) {
                $this->commandBus->dispatch(new ScheduleEdition($editionId));
            } elseif (str_contains($uriTemplate, '/unschedule')) {
                $this->commandBus->dispatch(new UnscheduleEdition($editionId));
            }
        } catch (EditionNotFoundException $e) {
            throw new MissingModelException(Edition::class, $e);
        }

        return new AdminEdition(id: (string) $editionId);
    }
}
