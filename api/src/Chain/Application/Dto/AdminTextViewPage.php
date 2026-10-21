<?php

declare(strict_types=1);

namespace App\Chain\Application\Dto;

/**
 * One page of the admin text library listing.
 */
final readonly class AdminTextViewPage
{
    /**
     * @param AdminTextView[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $itemsPerPage,
    ) {
    }
}
