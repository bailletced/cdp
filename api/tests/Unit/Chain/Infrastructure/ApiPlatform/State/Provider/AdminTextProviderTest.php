<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Chain\Application\Dto\AdminTextDetailsView;
use App\Chain\Application\Dto\AdminTextView;
use App\Chain\Application\Dto\AdminTextViewPage;
use App\Chain\Application\Query\AdminTextViewFinder;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminTextResource;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminTextProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AdminTextProviderTest extends TestCase
{
    public function testProvideCollectionReturnsTheFinderPageAsAPaginator(): void
    {
        $finder = new class implements AdminTextViewFinder {
            public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
            {
                return new AdminTextViewPage([
                    new AdminTextView('id-1', 'Oraison de Thérèse', 'Thérèse de Lisieux', 2),
                ], 5, 1, 20);
            }

            public function findOne(TextId $id): ?AdminTextDetailsView
            {
                return null;
            }
        };

        $provider = new AdminTextProvider($finder);

        $result = $provider->provide($this->createStub(Operation::class), [], []);

        self::assertInstanceOf(PaginatorInterface::class, $result);
        self::assertSame(5.0, $result->getTotalItems());
        self::assertSame(1.0, $result->getCurrentPage());
        self::assertSame(20.0, $result->getItemsPerPage());

        $items = iterator_to_array($result, false);
        self::assertCount(1, $items);
        self::assertInstanceOf(AdminTextResource::class, $items[0]);
        self::assertSame('id-1', $items[0]->id);
        self::assertSame('Oraison de Thérèse', $items[0]->title);
        self::assertSame('Thérèse de Lisieux', $items[0]->source);
        self::assertSame(2, $items[0]->attributionCount);
        self::assertNull($items[0]->body);
    }

    public function testProvideCollectionPassesTheSearchQueryAndPaginationToTheFinder(): void
    {
        $finder = new class implements AdminTextViewFinder {
            public ?string $lastQuery = null;

            public ?int $lastPage = null;

            public ?int $lastItemsPerPage = null;

            public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
            {
                $this->lastQuery = $query;
                $this->lastPage = $page;
                $this->lastItemsPerPage = $itemsPerPage;

                return new AdminTextViewPage([], 0, $page, $itemsPerPage);
            }

            public function findOne(TextId $id): ?AdminTextDetailsView
            {
                return null;
            }
        };

        $provider = new AdminTextProvider($finder);
        $provider->provide($this->createStub(Operation::class), [], [
            'filters' => ['q' => 'therese', 'page' => '2', 'itemsPerPage' => '10'],
        ]);

        self::assertSame('therese', $finder->lastQuery);
        self::assertSame(2, $finder->lastPage);
        self::assertSame(10, $finder->lastItemsPerPage);
    }

    public function testProvideCollectionClampsInvalidPaginationFilters(): void
    {
        $finder = new class implements AdminTextViewFinder {
            public ?int $lastPage = null;

            public ?int $lastItemsPerPage = null;

            public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
            {
                $this->lastPage = $page;
                $this->lastItemsPerPage = $itemsPerPage;

                return new AdminTextViewPage([], 0, $page, $itemsPerPage);
            }

            public function findOne(TextId $id): ?AdminTextDetailsView
            {
                return null;
            }
        };

        $provider = new AdminTextProvider($finder);
        $provider->provide($this->createStub(Operation::class), [], [
            'filters' => ['page' => '0', 'itemsPerPage' => '500'],
        ]);

        self::assertSame(1, $finder->lastPage);
        self::assertSame(100, $finder->lastItemsPerPage);
    }

    public function testProvideItemReturnsTheTextDetailsAsAResource(): void
    {
        $id = Uuid::v7()->toRfc4122();
        $details = new AdminTextDetailsView($id, 'Oraison de Thérèse', 'Thérèse de Lisieux', '<p>Corps <strong>riche</strong>.</p>', 'Méditer le matin.', 3);
        $finder = new class($details) implements AdminTextViewFinder {
            public function __construct(
                private readonly AdminTextDetailsView $details,
            ) {
            }

            public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
            {
                return new AdminTextViewPage([], 0, $page, $itemsPerPage);
            }

            public function findOne(TextId $id): ?AdminTextDetailsView
            {
                return $this->details;
            }
        };

        $provider = new AdminTextProvider($finder);

        $result = $provider->provide($this->createStub(Operation::class), ['id' => $id], []);

        self::assertInstanceOf(AdminTextResource::class, $result);
        self::assertSame($id, $result->id);
        self::assertSame('Oraison de Thérèse', $result->title);
        self::assertSame('Thérèse de Lisieux', $result->source);
        self::assertSame('<p>Corps <strong>riche</strong>.</p>', $result->body);
        self::assertSame('Méditer le matin.', $result->hints);
        self::assertSame(3, $result->attributionCount);
    }

    public function testProvideItemReturnsNullWhenTheTextIsUnknown(): void
    {
        $finder = new class implements AdminTextViewFinder {
            public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
            {
                return new AdminTextViewPage([], 0, $page, $itemsPerPage);
            }

            public function findOne(TextId $id): ?AdminTextDetailsView
            {
                return null;
            }
        };

        $provider = new AdminTextProvider($finder);

        $result = $provider->provide($this->createStub(Operation::class), ['id' => Uuid::v7()->toRfc4122()], []);

        self::assertNull($result);
    }

    public function testProvideItemReturnsNullWhenTheIdIsNotAValidUuid(): void
    {
        $finder = new class implements AdminTextViewFinder {
            public function search(string $query, int $page, int $itemsPerPage): AdminTextViewPage
            {
                return new AdminTextViewPage([], 0, $page, $itemsPerPage);
            }

            public function findOne(TextId $id): ?AdminTextDetailsView
            {
                return null;
            }
        };

        $provider = new AdminTextProvider($finder);

        $result = $provider->provide($this->createStub(Operation::class), ['id' => 'id-1'], []);

        self::assertNull($result);
    }
}
