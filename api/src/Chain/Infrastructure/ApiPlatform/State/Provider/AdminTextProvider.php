<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Chain\Application\Dto\AdminTextDetailsView;
use App\Chain\Application\Dto\AdminTextView;
use App\Chain\Application\Query\AdminTextViewFinder;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminTextResource;
use App\Chain\Infrastructure\ApiPlatform\State\Pagination\AdminTextPaginator;

/**
 * Provides AdminTextResource resources for GET collection and GET item
 * operations (F06-T08).
 *
 * The collection is paginated and searched through the AdminTextViewFinder
 * port: the q filter (title/source, accent-insensitive) and the standard
 * page / itemsPerPage pagination parameters are forwarded to the finder, and
 * the finder's total is carried by the paginator so Hydra emits totalItems
 * and the pagination view.
 *
 * An item id that is not a valid UUID yields null (404) instead of an error.
 *
 * @implements ProviderInterface<AdminTextResource>
 */
final readonly class AdminTextProvider implements ProviderInterface
{
    private const int DEFAULT_ITEMS_PER_PAGE = 20;

    private const int MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private AdminTextViewFinder $finder,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminTextResource|AdminTextPaginator|null
    {
        if (isset($uriVariables['id']) && \is_string($uriVariables['id'])) {
            try {
                $textId = new TextId($uriVariables['id']);
            } catch (\InvalidArgumentException) {
                return null;
            }

            $view = $this->finder->findOne($textId);

            if (null === $view) {
                return null;
            }

            return $this->toResource($view);
        }

        $filters = $context['filters'] ?? [];
        \assert(\is_array($filters));

        $query = $filters['q'] ?? '';
        $query = \is_string($query) ? $query : '';

        $pageView = $this->finder->search(
            $query,
            self::positiveInt($filters['page'] ?? null, 1, \PHP_INT_MAX),
            self::positiveInt($filters['itemsPerPage'] ?? null, self::DEFAULT_ITEMS_PER_PAGE, self::MAX_ITEMS_PER_PAGE),
        );

        $items = array_map(
            static fn (AdminTextView $view): AdminTextResource => new AdminTextResource(
                id: $view->id,
                title: $view->title,
                source: $view->source,
                attributionCount: $view->attributionCount,
            ),
            $pageView->items,
        );

        return new AdminTextPaginator($items, $pageView->page, $pageView->itemsPerPage, $pageView->total);
    }

    private function toResource(AdminTextDetailsView $view): AdminTextResource
    {
        return new AdminTextResource(
            id: $view->id,
            title: $view->title,
            source: $view->source,
            body: $view->body,
            hints: $view->hints,
            attributionCount: $view->attributionCount,
        );
    }

    private static function positiveInt(mixed $value, int $default, int $max): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return min(max(1, (int) $value), $max);
    }
}
