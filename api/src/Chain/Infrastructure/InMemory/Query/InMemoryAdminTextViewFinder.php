<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\InMemory\Query;

use App\Chain\Application\Dto\AdminTextDetailsView;
use App\Chain\Application\Dto\AdminTextView;
use App\Chain\Application\Dto\AdminTextViewPage;
use App\Chain\Application\Query\AdminTextViewFinder;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\TextId;
use Transliterator;

/**
 * In-memory implementation of AdminTextViewFinder for testing.
 *
 * Mirrors the Doctrine implementation: case- and accent-insensitive search on
 * title and source, alphabetical order, pagination and attribution counts
 * computed from the editions.
 */
final readonly class InMemoryAdminTextViewFinder implements AdminTextViewFinder
{
    public function __construct(
        private TextRepositoryInterface $textRepository,
        private EditionRepositoryInterface $editionRepository,
    ) {
    }

    public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
    {
        $query = trim($query);
        $page = max(1, $page);
        $itemsPerPage = max(1, $itemsPerPage);

        $matches = [];
        foreach ($this->textRepository->findAll() as $text) {
            if ('' !== $query
                && !str_contains(self::normalize($text->getTitle()->value), self::normalize($query))
                && !str_contains(self::normalize($text->getSource()->value), self::normalize($query))
            ) {
                continue;
            }
            $matches[] = $text;
        }

        usort($matches, static fn (Text $a, Text $b): int => [
            self::normalize($a->getTitle()->value),
            (string) $a->getId(),
        ] <=> [
            self::normalize($b->getTitle()->value),
            (string) $b->getId(),
        ]);

        $total = \count($matches);
        $items = [];
        foreach (\array_slice($matches, ($page - 1) * $itemsPerPage, $itemsPerPage) as $text) {
            $items[] = $this->toView($text);
        }

        return new AdminTextViewPage($items, $total, $page, $itemsPerPage);
    }

    public function findOne(TextId $id): ?AdminTextDetailsView
    {
        $text = $this->textRepository->findById($id);

        if (null === $text) {
            return null;
        }

        return new AdminTextDetailsView(
            id: (string) $text->getId(),
            title: $text->getTitle()->value,
            source: $text->getSource()->value,
            body: $text->getBody()->value,
            hints: $text->getHints(),
            attributionCount: $this->countAttributions($text->getId()),
        );
    }

    private function toView(Text $text): AdminTextView
    {
        return new AdminTextView(
            id: (string) $text->getId(),
            title: $text->getTitle()->value,
            source: $text->getSource()->value,
            attributionCount: $this->countAttributions($text->getId()),
        );
    }

    private function countAttributions(TextId $textId): int
    {
        $count = 0;
        foreach ($this->editionRepository->findAll() as $edition) {
            $count += $edition->countTextAttributions($textId);
        }

        return $count;
    }

    /**
     * Same normalization as the SQL unaccent(lower(...)) of the Doctrine
     * implementation: transliteration to ASCII, then lower case.
     */
    private static function normalize(string $value): string
    {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII');

        return mb_strtolower((string) $transliterator?->transliterate($value));
    }
}
