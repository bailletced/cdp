<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

/**
 * Admin resource for managing editions (F05-T14, F05-T15, F05-T16).
 */
#[ApiResource(
    shortName: 'AdminEdition',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/editions',
            provider: AdminEditionProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Get(
            uriTemplate: '/admin/editions/{id}',
            provider: AdminEditionProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/editions',
            processor: AdminEditionProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Patch(
            uriTemplate: '/admin/editions/{id}',
            processor: AdminEditionProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/editions/{id}/schedule',
            processor: AdminEditionScheduleProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/editions/{id}/unschedule',
            processor: AdminEditionScheduleProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
    ],
)]
final class AdminEdition
{
    /**
     * @param array<array{rank: int, startsAt: string, endsAt: string, label: string, partOfDay: string, textId: string|null}>|null $slots
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $title = null,
        public readonly ?string $presentation = null,
        public readonly ?string $startsAt = null,
        public readonly ?string $opensAt = null,
        public readonly ?string $resolvedStartsAtUtc = null,
        public readonly ?string $resolvedStartsAtLabel = null,
        public readonly ?string $lifecycle = null,
        public readonly ?string $status = null,
        public readonly ?string $scheduledAt = null,
        public readonly ?int $version = null,
        public readonly ?array $slots = null,
    ) {
    }
}
