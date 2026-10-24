<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Chain\Application\Dto\AdminEditionView;
use App\Chain\Application\Dto\AdminSlotView;
use App\Chain\Application\Query\AdminEditionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\ApiPlatform\Resource\AdminEditionResource;
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
                        new AdminSlotView('slot1', 1, '2026-11-28T19:00:00+00:00', '2026-11-28T19:30:00+00:00', '20h00–20h30', 'evening', null, null, null, false),
                    ], 1, []),
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

    public function testProvideItemExposesTheTextOfEachSlotAndTheWarnings(): void
    {
        $textId = '01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b';
        $finder = new class($textId) implements AdminEditionViewFinder {
            public function __construct(private readonly string $textId)
            {
            }

            public function findAll(): array
            {
                return [];
            }

            public function findOne(EditionId $id): ?AdminEditionView
            {
                return new AdminEditionView('id1', 'Titre', '', '2026-11-28T19:00:00+00:00', '2026-11-13T19:00:00+00:00', 'draft', 'draft', null, 1, [
                    new AdminSlotView('slot1', 1, '2026-11-28T19:00:00+00:00', '2026-11-28T19:30:00+00:00', '20h00–20h30', 'evening', $this->textId, 'Que rien ne te trouble', 'Thérèse d’Avila', true),
                ], 47, [$this->textId]);
            }
        };

        $resource = new AdminEditionProvider($finder)->provide($this->createStub(Operation::class), ['id' => '01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8c']);

        self::assertInstanceOf(AdminEditionResource::class, $resource);
        self::assertSame([[
            'id' => 'slot1',
            'rank' => 1,
            'startsAt' => '2026-11-28T19:00:00+00:00',
            'endsAt' => '2026-11-28T19:30:00+00:00',
            'label' => '20h00–20h30',
            'partOfDay' => 'evening',
            'textId' => $textId,
            'textTitle' => 'Que rien ne te trouble',
            'textSource' => 'Thérèse d’Avila',
            'textAlreadyUsed' => true,
        ]], $resource->slots);
        self::assertSame(47, $resource->slotsWithoutText);
        self::assertSame([$textId], $resource->duplicatedTextIds);
    }
}
