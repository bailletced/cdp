<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminEditionResource;

/**
 * Provides AdminEditionResource resources for GET collection and GET item operations.
 *
 * @implements ProviderInterface<AdminEditionResource>
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminEditionResource|ArrayPaginator|null
    {
        if (isset($uriVariables['id']) && \is_string($uriVariables['id'])) {
            $view = $this->finder->findOne(new EditionId($uriVariables['id']));

            if (null === $view) {
                return null;
            }

            return AdminEditionResource::fromView($view);
        }

        $views = $this->finder->findAll();
        $resources = array_map(static fn (AdminEditionView $view): AdminEditionResource => AdminEditionResource::fromView($view), $views);

        return new ArrayPaginator($resources, 0, \count($resources));
    }
}
