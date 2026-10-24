<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Dto\PlanningParticipantView;
use App\Chain\Application\Dto\PlanningSlotView;
use App\Chain\Application\Query\PlanningViewFinder;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Policy\VisibilityPolicy;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\Resource\PublicEditionSlotsResource;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Provides the public planning grid for GET /api/editions/{id}/slots (F08-T04).
 *
 * The PlanningViewFinder only returns scheduled (public) editions: an
 * unknown id, an invalid UUID or a draft edition all answer 404, so the
 * front can fall back to the "no edition yet" state.
 *
 * F10-T08: the participants (names and pointages) are masked to null unless
 * the VisibilityPolicy lets the viewer see them — a masked field is always
 * present and null, never absent. `mine` marks the slots of the viewer.
 *
 * @implements ProviderInterface<PublicEditionSlotsResource>
 */
final readonly class PublicEditionSlotsProvider implements ProviderInterface
{
    public function __construct(
        private PlanningViewFinder $finder,
        private CurrentViewer $currentViewer,
        private VisibilityPolicy $visibilityPolicy,
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

        $viewer = $this->currentViewer->current();
        $namesVisible = $this->visibilityPolicy->canSeeParticipantNames($viewer, $editionId);
        $mySlotIds = array_map(
            static fn (Inscription $inscription): string => (string) $inscription->getSlotId(),
            $this->visibilityPolicy->inscriptionsOf($viewer),
        );

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
                    'registrationCount' => $slot->registrationCount,
                    'occupied' => $slot->occupied,
                    'participants' => $namesVisible ? array_map(
                        static fn (PlanningParticipantView $participant): array => ['name' => $participant->displayName, 'prayed' => $participant->prayed],
                        $slot->participants,
                    ) : null,
                    'mine' => \in_array($slot->id, $mySlotIds, true),
                ],
                $view->slots,
            ),
        );
    }
}
