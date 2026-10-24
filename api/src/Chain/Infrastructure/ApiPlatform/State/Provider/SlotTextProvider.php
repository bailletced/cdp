<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Query\SlotTextViewFinder;
use App\Chain\Domain\Policy\VisibilityPolicy;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\ApiPlatform\Resource\SlotTextResource;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Provides the reading page of a slot for GET /api/slots/{id}/text
 * (F12-T02). The VisibilityPolicy decides (RG-TX-1): a viewer who may not
 * read the text gets the same 404 as for an unknown slot.
 *
 * @implements ProviderInterface<SlotTextResource>
 */
final readonly class SlotTextProvider implements ProviderInterface
{
    public function __construct(
        private SlotTextViewFinder $finder,
        private CurrentViewer $currentViewer,
        private VisibilityPolicy $visibilityPolicy,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SlotTextResource
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new MissingModelException(SlotTextResource::class);
        }

        try {
            $slotId = new SlotId($uriVariables['id']);
        } catch (\InvalidArgumentException) {
            throw new MissingModelException(SlotTextResource::class);
        }

        if (!$this->visibilityPolicy->canReadText($this->currentViewer->current(), $slotId)) {
            throw new MissingModelException(SlotTextResource::class);
        }

        $view = $this->finder->findBySlot($slotId);

        if (null === $view) {
            throw new MissingModelException(SlotTextResource::class);
        }

        return new SlotTextResource(
            id: $view->slotId,
            editionId: $view->editionId,
            editionTitle: $view->editionTitle,
            startsAt: $view->startsAt->format(\DATE_ATOM),
            endsAt: $view->endsAt->format(\DATE_ATOM),
            label: $view->label,
            dayLabel: $view->dayLabel,
            text: null === $view->text ? null : [
                'title' => $view->text->title,
                'source' => $view->text->source,
                'body' => $view->text->body,
                'hints' => $view->text->hints,
            ],
        );
    }
}
