<?php

declare(strict_types=1);

namespace App\Chain\Domain\Policy;

use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Repository\InscriptionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\InscriptionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Domain\Security\Viewer;

/**
 * Single decision point of the rights matrix (specs-fonctionnelles §4,
 * specs-techniques §6.5). Providers ask it which fields to mask, voters ask
 * it which actions to allow.
 *
 * The viewer's inscriptions are those of its priant once verified, plus those
 * covered by the pass of its device (RG-IN-6). They are read on each call:
 * a cancelled inscription gives nothing anymore.
 */
final readonly class VisibilityPolicy
{
    public function __construct(
        private InscriptionRepositoryInterface $inscriptionRepository,
    ) {
    }

    /**
     * RG-PL-4, RG-LV-2: the names of the priants and their pointages are seen
     * by the inscrits of the edition and by the organizers.
     */
    public function canSeeParticipantNames(Viewer $viewer, EditionId $editionId): bool
    {
        return $viewer->isOrganizer || $this->isRegisteredIn($viewer, $editionId);
    }

    /**
     * The intentions (and the relay feed) are seen by the inscrits of the
     * edition and by the organizers.
     */
    public function canSeeIntentions(Viewer $viewer, EditionId $editionId): bool
    {
        return $viewer->isOrganizer || $this->isRegisteredIn($viewer, $editionId);
    }

    /**
     * The inscriptions of the viewer: its own once verified, and those
     * covered by the pass of its device.
     *
     * @return list<Inscription>
     */
    public function inscriptionsOf(Viewer $viewer): array
    {
        $priantId = $viewer->verified && null !== $viewer->priantId ? new PriantId($viewer->priantId) : null;
        $covered = array_map(static fn (string $id): InscriptionId => new InscriptionId($id), $viewer->coveredInscriptionIds);

        if (null === $priantId && [] === $covered) {
            return [];
        }

        return $this->inscriptionRepository->findOwnedOrCovered($priantId, array_values($covered));
    }

    private function isRegisteredIn(Viewer $viewer, EditionId $editionId): bool
    {
        return array_any(
            $this->inscriptionsOf($viewer),
            static fn (Inscription $inscription): bool => $inscription->getEditionId()->equals($editionId),
        );
    }
}
