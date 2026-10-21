<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Dto\AdminSlotView;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;

/**
 * Provides AdminEdition resources for GET collection and GET item operations.
 *
 * @implements ProviderInterface<AdminEdition>
 */
final readonly class AdminEditionProvider implements ProviderInterface
{
    public function __construct(
        private AdminEditionViewFinder $finder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminEdition|ArrayPaginator|null
    {
        if (isset($uriVariables['id']) && \is_string($uriVariables['id'])) {
            $view = $this->finder->findOne(new EditionId($uriVariables['id']));

            if (null === $view) {
                return null;
            }

            return $this->toResource($view);
        }

        $views = $this->finder->findAll();
        $resources = array_map(fn (AdminEditionView $view): AdminEdition => $this->toResource($view), $views);

        return new ArrayPaginator($resources, 0, \count($resources));
    }

    private function toResource(AdminEditionView $view): AdminEdition
    {
        return new AdminEdition(
            id: $view->id,
            title: $view->title,
            presentation: $view->presentation,
            startsAt: $view->startsAtUtc,
            opensAt: $view->opensAtUtc,
            lifecycle: $view->lifecycle,
            status: $view->status,
            scheduledAt: $view->scheduledAtUtc,
            version: $view->version,
            slots: array_map(static fn (AdminSlotView $slot): array => [
                'rank' => $slot->rank,
                'startsAt' => $slot->startsAtUtc,
                'endsAt' => $slot->endsAtUtc,
                'label' => $slot->label,
                'partOfDay' => $slot->partOfDay,
                'textId' => $slot->textId,
            ], $view->slots),
        );
    }
}
