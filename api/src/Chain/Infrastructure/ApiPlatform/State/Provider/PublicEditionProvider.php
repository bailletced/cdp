<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Infrastructure\ApiPlatform\Resource\PublicEditionResource;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;

/**
 * Provides the current public edition for GET /api/editions/current (F08-T02).
 *
 * Returns 404 (MissingModelException) when no edition is open, so the front
 * can display the "no edition yet" state (arbitrage Q18).
 *
 * @implements ProviderInterface<PublicEditionResource>
 */
final readonly class PublicEditionProvider implements ProviderInterface
{
    public function __construct(
        private PublicEditionViewFinder $finder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PublicEditionResource
    {
        $view = $this->finder->current();

        if (null === $view) {
            throw new MissingModelException(PublicEditionResource::class);
        }

        return new PublicEditionResource(
            id: $view->id,
            title: $view->title,
            presentation: $view->presentation,
            startsAt: $view->startsAtUtc,
            opensAt: $view->opensAtUtc,
            startsAtLabel: $view->startsAtLabel,
            opensAtLabel: $view->opensAtLabel,
            status: $view->status,
        );
    }
}
