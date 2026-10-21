<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\ApiPlatform\State\Provider;

use App\Access\Infrastructure\ApiPlatform\State\Processor\AdminOrganizerProcessor;
use App\Access\Infrastructure\ApiPlatform\State\Provider\AdminOrganizerProvider;
use PHPUnit\Framework\TestCase;

final class AdminOrganizerProviderTest extends TestCase
{
    public function testItExists(): void
    {
        self::assertTrue(class_exists(AdminOrganizerProvider::class));
    }

    public function testProcessorItExists(): void
    {
        self::assertTrue(class_exists(AdminOrganizerProcessor::class));
    }

    public function testItProvidesACollectionOfOrganizers(): void
    {
        $finder = new class implements \App\Access\Application\Query\OrganizerViewFinder {
            public function findAll(): array
            {
                return [
                    new \App\Access\Application\Dto\OrganizerView('id-1', 'anne@bailletced.org', 'anne@bailletced.org', '2026-10-21T10:00:00+00:00'),
                ];
            }

            public function findById(string $organizerId): ?\App\Access\Application\Dto\OrganizerView
            {
                return null;
            }

            public function findByEmail(\App\Shared\Domain\ValueObject\Email $email): ?\App\Access\Application\Dto\OrganizerView
            {
                return null;
            }
        };

        $provider = new AdminOrganizerProvider($finder);
        $operation = $this->createMock(\ApiPlatform\Metadata\Operation::class);

        $result = $provider->provide($operation);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame('anne@bailletced.org', $result[0]->email);
    }
}
