<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionResult;
use App\Chain\Application\UpdateEdition;
use App\Chain\Application\UpdateEditionResult;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Processes AdminEdition resources for POST (create) and PATCH (update) operations.
 *
 * Invalid payloads answer 422 with one violation per offending property:
 * a value object rejection is mapped to its property, and the date rules
 * (parseable, on the full or half hour, opening before start) are checked
 * before the command is dispatched.
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
        [$startsAt, $opensAt] = $this->parseParisDates($data->startsAt, $data->opensAt);

        $result = $this->commandBus->dispatch(new CreateEdition(
            $this->toTitle($data->title),
            $this->toPresentation($data->presentation),
            $startsAt,
            $opensAt,
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
        [$startsAt, $opensAt] = $this->parseParisDates($data->startsAt, $data->opensAt);

        // Domain exceptions propagate: the DomainException mechanism maps them to 404/409.
        $result = $this->commandBus->dispatch(new UpdateEdition(
            $editionId,
            $this->toTitle($data->title),
            $this->toPresentation($data->presentation),
            $startsAt,
            $opensAt,
            $expectedVersion,
        ));

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

    private function toTitle(?string $title): EditionTitle
    {
        try {
            return new EditionTitle($title ?? '');
        } catch (\InvalidArgumentException $e) {
            throw self::violation('title', $e->getMessage());
        }
    }

    private function toPresentation(?string $presentation): Presentation
    {
        try {
            return new Presentation($presentation ?? '');
        } catch (\InvalidArgumentException $e) {
            throw self::violation('presentation', $e->getMessage());
        }
    }

    /**
     * Parses the payload dates and enforces the input rules the organizer form
     * relies on: a parseable date-time, on the full or half hour (RG-ED-2),
     * with the opening before the start.
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function parseParisDates(?string $startsAt, ?string $opensAt): array
    {
        $start = $this->parseParisDate($startsAt, 'startsAt', 'du début de l\'édition');
        $open = $this->parseParisDate($opensAt, 'opensAt', 'de l\'ouverture des inscriptions');

        if ($open >= $start) {
            throw self::violation('opensAt', "L'ouverture des inscriptions doit être antérieure au début de l'édition.");
        }

        return [$start, $open];
    }

    private function parseParisDate(?string $value, string $propertyPath, string $label): \DateTimeImmutable
    {
        if (null === $value || '' === trim($value)) {
            throw self::violation($propertyPath, "La date et l'heure {$label} sont requises.");
        }

        try {
            $date = new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw self::violation($propertyPath, "La date et l'heure {$label} sont invalides.");
        }

        $minutes = (int) $date->format('i');

        if (0 !== $minutes && 30 !== $minutes) {
            throw self::violation($propertyPath, "L'heure {$label} doit tomber sur l'heure pleine ou la demi-heure (heure de Paris).");
        }

        return $date;
    }

    private static function violation(string $propertyPath, string $message): ValidationException
    {
        return new ValidationException(new ConstraintViolationList([
            new ConstraintViolation($message, null, [], null, $propertyPath, null),
        ]));
    }
}
