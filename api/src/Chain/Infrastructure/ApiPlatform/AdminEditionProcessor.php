<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionResult;
use App\Chain\Application\UpdateEdition;
use App\Chain\Application\UpdateEditionResult;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Exception\EditionNotFoundException;
use App\Chain\Domain\Exception\EditionVersionConflictException;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Infrastructure\ApiPlatform\Exception\ConflictException;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Processes AdminEdition resources for POST (create) and PATCH (update) operations.
 *
 * @implements ProcessorInterface<AdminEdition, AdminEdition>
 */
final readonly class AdminEditionProcessor implements ProcessorInterface
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
        if ($operation instanceof Post) {
            return $this->handleCreate($data);
        }

        if ($operation instanceof Patch) {
            return $this->handleUpdate($data, $uriVariables);
        }

        throw new \LogicException(\sprintf('Unsupported operation %s.', $operation::class));
    }

    private function handleCreate(AdminEdition $data): AdminEdition
    {
        $result = $this->commandBus->dispatch(new CreateEdition(
            new EditionTitle($data->title ?? ''),
            new Presentation($data->presentation ?? ''),
            new \DateTimeImmutable($data->startsAt ?? 'now'),
            new \DateTimeImmutable($data->opensAt ?? 'now'),
        ));

        if (!$result instanceof CreateEditionResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', CreateEditionResult::class, get_debug_type($result)));
        }

        return new AdminEdition(
            id: (string) $result->editionId,
            title: $data->title,
            presentation: $data->presentation,
            startsAt: $data->startsAt,
            opensAt: $data->opensAt,
            resolvedStartsAtUtc: $result->resolvedStartsAtUtc->format(\DateTimeInterface::ATOM),
            resolvedStartsAtLabel: $result->resolvedStartsAtLabel,
            lifecycle: 'draft',
            status: 'draft',
            version: 1,
        );
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function handleUpdate(AdminEdition $data, array $uriVariables): AdminEdition
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new \LogicException('Missing edition identifier in URI variables.');
        }

        $editionId = new EditionId($uriVariables['id']);
        $expectedVersion = $data->version ?? 1;

        try {
            $result = $this->commandBus->dispatch(new UpdateEdition(
                $editionId,
                new EditionTitle($data->title ?? ''),
                new Presentation($data->presentation ?? ''),
                new \DateTimeImmutable($data->startsAt ?? 'now'),
                new \DateTimeImmutable($data->opensAt ?? 'now'),
                $expectedVersion,
            ));
        } catch (EditionNotFoundException $e) {
            throw new MissingModelException(Edition::class, $e);
        } catch (EditionVersionConflictException $e) {
            throw new ConflictException($e->getMessage(), $e);
        }

        if (!$result instanceof UpdateEditionResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', UpdateEditionResult::class, get_debug_type($result)));
        }

        return new AdminEdition(
            id: (string) $editionId,
            title: $data->title,
            presentation: $data->presentation,
            startsAt: $data->startsAt,
            opensAt: $data->opensAt,
            resolvedStartsAtUtc: $result->resolvedStartsAtUtc->format(\DateTimeInterface::ATOM),
            resolvedStartsAtLabel: $result->resolvedStartsAtLabel,
            version: $expectedVersion + 1,
        );
    }
}
