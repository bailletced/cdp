<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\State\Pagination;

use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminTextResource;

/**
 * Paginator over one page of AdminTextResource items, carrying the total
 * count of the (filtered) collection so Hydra emits hydra:totalItems and the
 * hydra:view pagination links.
 *
 * @implements \IteratorAggregate<mixed, AdminTextResource>
 * @implements PaginatorInterface<AdminTextResource>
 */
final class AdminTextPaginator implements \IteratorAggregate, PaginatorInterface
{
    /**
     * @param AdminTextResource[] $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $page,
        private readonly int $itemsPerPage,
        private readonly int $totalItems,
    ) {
    }

    public function getCurrentPage(): float
    {
        return (float) $this->page;
    }

    public function getLastPage(): float
    {
        if ($this->itemsPerPage < 1) {
            return 1.;
        }

        return max((float) (int) ceil($this->totalItems / $this->itemsPerPage), 1.);
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->itemsPerPage;
    }

    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }
}
