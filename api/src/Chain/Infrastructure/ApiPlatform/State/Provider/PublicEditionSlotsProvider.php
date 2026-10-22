<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Dto\PlanningSlotView;
use App\Chain\Application\Query\PlanningViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\Resource\PublicEditionSlotsResource;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Provides the public planning grid for GET /api/editions/{id}/slots (F08-T04).
 *
 * The PlanningViewFinder only returns scheduled (public) editions: an
 * unknown id, an invalid UUID or a draft edition all answer 404, so the
 * front can fall back to the "no edition yet" state.
 *
 * @implements ProviderInterface<PublicEditionSlotsResource>
 */
final readonly class PublicEditionSlotsProvider implements ProviderInterface
{
    public function __construct(
        private PlanningViewFinder $finder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PublicEditionSlotsResource
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new MissingModelException(PublicEditionSlotsResource::class);
        }

        try {
            $editionId = new EditionId($uriVariables['id']);
        } catch (\InvalidArgumentException) {
            throw new MissingModelException(PublicEditionSlotsResource::class);
        }

        $view = $this->finder->findByEdition($editionId);

        if (null === $view) {
            throw new MissingModelException(PublicEditionSlotsResource::class);
        }

        return new PublicEditionSlotsResource(
            id: $view->editionId,
            slots: array_map(
                static fn (PlanningSlotView $slot): array => [
                    'id' => $slot->id,
                    'rank' => $slot->rank,
                    'startsAt' => $slot->startsAtUtc,
                    'endsAt' => $slot->endsAtUtc,
                    'label' => $slot->label,
                    'partOfDay' => $slot->partOfDay,
                    'state' => $slot->state,
                    'textTitle' => $slot->textTitle,
                    'textSource' => $slot->textSource,
                ],
                $view->slots,
            ),
        );
    }
}
