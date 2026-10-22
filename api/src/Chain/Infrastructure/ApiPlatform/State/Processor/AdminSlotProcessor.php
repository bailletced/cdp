<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Chain\Application\AssignText;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminSlotResource;
use App\Shared\Application\Command\CommandBusInterface;

/**
 * Processes PUT /api/admin/slots/{id}/text (F07-T03).
 *
 * Dispatches AssignText with the slot id from the URI and the text id from
 * the payload (null withdraws the attribution). Domain exceptions (404)
 * propagate to API Platform's error handling.
 *
 * @implements ProcessorInterface<AdminSlotResource, AdminSlotResource>
 */
final readonly class AdminSlotProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminSlotResource
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new \LogicException('Missing slot identifier in URI variables.');
        }

        $slotId = new SlotId($uriVariables['id']);
        $textId = null !== $data->textId ? new TextId($data->textId) : null;

        // EditionNotFoundException and TextNotFoundException propagate (404).
        $this->commandBus->dispatch(new AssignText($slotId, $textId));

        return new AdminSlotResource(
            id: (string) $slotId,
            textId: $data->textId,
        );
    }
}
