<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Access\Application\Query\OrganizerViewFinder;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Provides AdminOrganizer resources from the OrganizerViewFinder (F04-T02).
 *
 * @implements ProviderInterface<AdminOrganizer>
 */
final readonly class AdminOrganizerProvider implements ProviderInterface
{
    public function __construct(
        private OrganizerViewFinder $finder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return AdminOrganizer|array<AdminOrganizer>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminOrganizer|array
    {
        // Collection (GET /api/admin/organizers)
        if (!isset($uriVariables['id'])) {
            return array_map(
                fn (\App\Access\Application\Dto\OrganizerView $view): AdminOrganizer => $this->toResource($view),
                $this->finder->findAll(),
            );
        }

        // Item (DELETE /api/admin/organizers/{id} — the item is loaded before deletion)
        /** @var string $organizerId */
        $organizerId = $uriVariables['id'];

        $view = $this->finder->findById($organizerId);

        if (null === $view) {
            throw new MissingModelException('Organisateur introuvable.');
        }

        return $this->toResource($view);
    }

    private function toResource(\App\Access\Application\Dto\OrganizerView $view): AdminOrganizer
    {
        return new AdminOrganizer(
            id: $view->id,
            email: $view->email,
            addedByEmail: $view->addedByEmail,
            addedAt: $view->addedAt,
        );
    }
}
