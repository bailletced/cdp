<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Doctrine;

use App\Chain\Application\Dto\AdminTextDetailsView;
use App\Chain\Application\Dto\AdminTextView;
use App\Chain\Application\Dto\AdminTextViewPage;
use App\Chain\Application\Query\AdminTextViewFinder;
use App\Chain\Domain\ValueObject\TextId;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Doctrine implementation of AdminTextViewFinder.
 *
 * Raw SQL reads over the text table, LEFT JOINed with slot to count the
 * attributions of each text. Search is case- and accent-insensitive on both
 * sides through the Postgres unaccent() function (extension created by
 * migration), so « therese » finds « Thérèse ».
 *
 * @phpstan-type TextRow array{id: string, title: string, source: string, attribution_count: int|string}
 * @phpstan-type TextDetailsRow array{id: string, title: string, source: string, body: string, hints: string|null, attribution_count: int|string}
 */
final readonly class DoctrineAdminTextViewFinder implements AdminTextViewFinder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
    {
        $query = trim($query);
        $page = max(1, $page);
        $itemsPerPage = max(1, $itemsPerPage);

        [$where, $params] = $this->searchCondition($query);

        $total = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM text t $where",
            $params,
        );
        \assert(\is_int($total) || \is_string($total));

        /** @var list<TextRow> $rows */
        $rows = $this->connection->fetchAllAssociative(
            "SELECT t.id, t.title, t.source, COUNT(s.id) AS attribution_count
             FROM text t
             LEFT JOIN slot s ON s.text_id = t.id
             $where
             GROUP BY t.id
             ORDER BY unaccent(lower(t.title)) ASC, t.id ASC
             LIMIT ? OFFSET ?",
            [...$params, $itemsPerPage, ($page - 1) * $itemsPerPage],
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = new AdminTextView(
                id: Uuid::fromString($row['id'])->toRfc4122(),
                title: $row['title'],
                source: $row['source'],
                attributionCount: (int) $row['attribution_count'],
            );
        }

        return new AdminTextViewPage($items, (int) $total, $page, $itemsPerPage);
    }

    public function findOne(TextId $id): ?AdminTextDetailsView
    {
        /** @var TextDetailsRow|false $row */
        $row = $this->connection->fetchAssociative(
            'SELECT t.id, t.title, t.source, t.body, t.hints, COUNT(s.id) AS attribution_count
             FROM text t
             LEFT JOIN slot s ON s.text_id = t.id
             WHERE t.id = ?
             GROUP BY t.id',
            [$id->value->toRfc4122()],
        );

        if (false === $row) {
            return null;
        }

        return new AdminTextDetailsView(
            id: Uuid::fromString($row['id'])->toRfc4122(),
            title: $row['title'],
            source: $row['source'],
            body: $row['body'],
            hints: $row['hints'],
            attributionCount: (int) $row['attribution_count'],
        );
    }

    /**
     * Case- and accent-insensitive match on title or source. The query is
     * normalized by the same unaccent(lower(...)) expression as the columns,
     * so both sides agree; LIKE wildcards in the query are escaped.
     *
     * @return array{0: string, 1: list<string>} The SQL WHERE fragment and its parameters
     */
    private function searchCondition(string $query): array
    {
        if ('' === $query) {
            return ['', []];
        }

        $escaped = addcslashes($query, '\\%_');

        return [
            "WHERE unaccent(lower(t.title)) LIKE '%' || unaccent(lower(?)) || '%'
                OR unaccent(lower(t.source)) LIKE '%' || unaccent(lower(?)) || '%'",
            [$escaped, $escaped],
        ];
    }
}
