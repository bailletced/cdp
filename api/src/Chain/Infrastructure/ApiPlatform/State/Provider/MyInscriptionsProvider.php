<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Dto\MyInscriptionView;
use App\Chain\Application\Query\MySpaceViewFinder;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Chain\Infrastructure\ApiPlatform\Resource\MyInscriptionsResource;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides « Mon espace » for GET /api/me/inscriptions (F14-T02). A session
 * is required: a device pass only gives the read-only view (RG-IN-6).
 *
 * @implements ProviderInterface<MyInscriptionsResource>
 */
final readonly class MyInscriptionsProvider implements ProviderInterface
{
    public function __construct(
        private CurrentViewer $currentViewer,
        private PublicEditionViewFinder $publicEditionViewFinder,
        private MySpaceViewFinder $mySpaceViewFinder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MyInscriptionsResource
    {
        $viewer = $this->currentViewer->current();

        if (!$viewer->verified) {
            throw new UnauthenticatedException();
        }

        if (null === $viewer->priantId) {
            throw new AccessDeniedException();
        }

        $view = $this->mySpaceViewFinder->find(new PriantId($viewer->priantId), $this->editionId($context));

        if (null === $view) {
            throw new MissingModelException(MyInscriptionsResource::class);
        }

        return new MyInscriptionsResource(
            editionId: $view->editionId,
            editionTitle: $view->editionTitle,
            displayName: $view->displayName,
            inscriptions: array_map(
                static fn (MyInscriptionView $inscription): array => [
                    'id' => $inscription->inscriptionId,
                    'slotId' => $inscription->slotId,
                    'startsAt' => $inscription->startsAt->format(\DATE_ATOM),
                    'endsAt' => $inscription->endsAt->format(\DATE_ATOM),
                    'label' => $inscription->label,
                    'dayLabel' => $inscription->dayLabel,
                    'textTitle' => $inscription->textTitle,
                    'textSource' => $inscription->textSource,
                    'state' => $inscription->state,
                    'actions' => $inscription->actions,
                ],
                $view->inscriptions,
            ),
        );
    }

    /**
     * The edition asked for, else the public one.
     *
     * @param array<string, mixed> $context
     */
    private function editionId(array $context): EditionId
    {
        $request = $context['request'] ?? null;
        $asked = $request instanceof Request ? $request->query->getString('edition') : '';

        if ('' !== $asked) {
            try {
                return new EditionId($asked);
            } catch (\InvalidArgumentException) {
                throw new MissingModelException(MyInscriptionsResource::class);
            }
        }

        $public = $this->publicEditionViewFinder->current();

        if (null === $public) {
            throw new MissingModelException(MyInscriptionsResource::class);
        }

        return new EditionId($public->id);
    }
}
