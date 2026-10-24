<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\SuggestEmptySlot;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\SlotId;
use App\Chain\Infrastructure\ApiPlatform\Resource\SlotSuggestionResource;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the draw of an empty slot for
 * GET /api/editions/{id}/slot-suggestion (F13-T03). Malformed `near` or
 * `exclude` ids are ignored: they only steer the draw.
 *
 * @implements ProviderInterface<SlotSuggestionResource>
 */
final readonly class SlotSuggestionProvider implements ProviderInterface
{
    public function __construct(
        private SuggestEmptySlot $suggestEmptySlot,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SlotSuggestionResource
    {
        if (!isset($uriVariables['id']) || !\is_string($uriVariables['id'])) {
            throw new MissingModelException(SlotSuggestionResource::class);
        }

        try {
            $editionId = new EditionId($uriVariables['id']);
        } catch (\InvalidArgumentException) {
            throw new MissingModelException(SlotSuggestionResource::class);
        }

        $request = $context['request'] ?? null;
        $query = $request instanceof Request ? $request->query : null;
        $near = self::slotIds($query?->getString('near') ?? '');

        $suggestion = ($this->suggestEmptySlot)(
            $editionId,
            $near[0] ?? null,
            self::slotIds($query?->getString('exclude') ?? ''),
        );

        if (null === $suggestion) {
            throw new MissingModelException(SlotSuggestionResource::class);
        }

        return new SlotSuggestionResource(
            id: (string) $editionId,
            slotId: $suggestion->id,
            startsAt: $suggestion->startsAt->format(\DATE_ATOM),
            endsAt: $suggestion->endsAt->format(\DATE_ATOM),
            label: $suggestion->label,
            textTitle: $suggestion->textTitle,
            textSource: $suggestion->textSource,
        );
    }

    /**
     * @return list<SlotId>
     */
    private static function slotIds(string $commaSeparated): array
    {
        $ids = [];
        foreach (explode(',', $commaSeparated) as $id) {
            try {
                $ids[] = new SlotId(trim($id));
            } catch (\InvalidArgumentException) {
                // An unknown id only steers the draw: ignored
            }
        }

        return $ids;
    }
}
