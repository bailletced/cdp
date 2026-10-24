<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Chain\Application\Query\AdminEditionViewFinder;
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
 * Both answer the updated edition. Scheduling never blocks on missing texts:
 * it warns « X créneaux n’ont pas encore de texte » (RG-ED-4, F07-T05).
 *
 * @implements ProcessorInterface<AdminEditionResource, AdminEditionResource>
 */
final readonly class AdminEditionScheduleProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private AdminEditionViewFinder $finder,
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
        $scheduling = str_contains($uriTemplate, '/schedule');
        if ($scheduling) {
            $this->commandBus->dispatch(new ScheduleEdition($editionId));
        } elseif (str_contains($uriTemplate, '/unschedule')) {
            $this->commandBus->dispatch(new UnscheduleEdition($editionId));
        }

        $view = $this->finder->findOne($editionId);
        if (null === $view) {
            return new AdminEditionResource(id: (string) $editionId);
        }

        return AdminEditionResource::fromView($view, $scheduling ? self::missingTextsWarning($view->slotsWithoutText) : null);
    }

    private static function missingTextsWarning(int $slotsWithoutText): ?string
    {
        return match ($slotsWithoutText) {
            0 => null,
            1 => '1 créneau n’a pas encore de texte.',
            default => \sprintf('%d créneaux n’ont pas encore de texte.', $slotsWithoutText),
        };
    }
}
