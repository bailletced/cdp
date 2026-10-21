<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Access\Application\Dto\OrganizerView;
use App\Access\Application\Query\OrganizerViewFinder;
use App\Access\Infrastructure\ApiPlatform\Resource\AdminOrganizerResource;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Provides AdminOrganizerResource resources from the OrganizerViewFinder (F04-T02).
 *
 * @implements ProviderInterface<AdminOrganizerResource>
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
     * @return AdminOrganizerResource|array<AdminOrganizerResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminOrganizerResource|array
    {
        // Collection (GET /api/admin/organizers)
        if (!isset($uriVariables['id'])) {
            return array_map(
                fn (OrganizerView $view): AdminOrganizerResource => $this->toResource($view),
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

    private function toResource(OrganizerView $view): AdminOrganizerResource
    {
        return new AdminOrganizerResource(
            id: $view->id,
            email: $view->email,
            addedByEmail: $view->addedByEmail,
            addedAt: $view->addedAt,
        );
    }
}
