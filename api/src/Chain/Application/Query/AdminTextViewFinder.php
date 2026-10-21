<?php

declare(strict_types=1);

namespace App\Chain\Application\Query;

use App\Chain\Application\Dto\AdminTextDetailsView;
use App\Chain\Application\Dto\AdminTextViewPage;
use App\Chain\Domain\ValueObject\TextId;

/**
 * Read model for the admin text library (US-06.4): paginated listing with
 * accent-insensitive search on title and source, attribution counts, and
 * full details for a single text.
 */
interface AdminTextViewFinder
{
    /**
     * Search texts by title or source (case- and accent-insensitive),
     * ordered alphabetically, paginated.
     *
     * @param string $query        Free-text query; an empty string lists every text
     * @param int    $page         1-based page number
     * @param int    $itemsPerPage Page size
     */
    public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage;

    /**
     * Full details of one text (body and hints included), with its
     * attribution count.
     */
    public function findOne(TextId $id): ?AdminTextDetailsView;
}
