<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Dto\AdminSlotView;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminEditionProvider;
use PHPUnit\Framework\TestCase;

final class AdminEditionProviderTest extends TestCase
{
    public function testProvideCollectionReturnsPaginator(): void
    {
        $finder = new class implements AdminEditionViewFinder {
            public function findAll(): array
            {
                return [
                    new AdminEditionView('id1', 'Titre', '', '2026-11-28T19:00:00+00:00', '2026-11-13T19:00:00+00:00', 'draft', 'draft', null, 1, [
                        new AdminSlotView(1, '2026-11-28T19:00:00+00:00', '2026-11-28T19:30:00+00:00', '20h00–20h30', 'evening', null),
                    ]),
                ];
            }

            public function findOne(EditionId $id): ?AdminEditionView
            {
                return null;
            }
        };

        $provider = new AdminEditionProvider($finder);

        $operation = $this->createStub(Operation::class);
        $result = $provider->provide($operation, [], []);

        self::assertInstanceOf(PaginatorInterface::class, $result);
    }
}
