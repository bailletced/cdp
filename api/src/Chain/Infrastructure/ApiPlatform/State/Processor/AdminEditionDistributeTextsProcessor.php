<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Chain\Application\DistributeTexts;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminEditionResource;
use App\Shared\Application\Command\CommandBusInterface;

/**
 * Processes POST /api/admin/editions/{id}/distribute-texts (F07-T03).
 *
 * Dispatches DistributeTexts with the edition id from the URI and the text
 * ids + start rank from the payload. Domain exceptions (404) propagate.
 *
 * @implements ProcessorInterface<AdminEditionResource, AdminEditionResource>
 */
final readonly class AdminEditionDistributeTextsProcessor implements ProcessorInterface
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

        /** @var list<string> $textIdStrings */
        $textIdStrings = \is_array($data->textIds) ? $data->textIds : [];
        $textIds = array_map(static fn (string $id): TextId => new TextId($id), $textIdStrings);

        $fromRank = \is_int($data->fromRank) ? $data->fromRank : 1;

        // EditionNotFoundException and TextNotFoundException propagate (404).
        $this->commandBus->dispatch(new DistributeTexts($editionId, $textIds, $fromRank));

        return new AdminEditionResource(id: (string) $editionId);
    }
}
