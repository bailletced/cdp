<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminTextProvider;

/**
 * Admin resource for the text library (F06-T08 for the read operations,
 * F06-T09 for the writes).
 *
 * - GET /api/admin/texts       → paginated, searchable list (title, source, attribution count)
 * - GET /api/admin/texts/{id}  → full details (body and hints included)
 *
 * Reserved to organizers: AdminAccessListener answers 401 to visitors and 403
 * to authenticated non-organizers on /api/admin/*.
 */
#[ApiResource(
    shortName: 'AdminText',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/texts',
            provider: AdminTextProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
            paginationItemsPerPage: 20,
            paginationMaximumItemsPerPage: 100,
            parameters: [
                'q' => new QueryParameter(
                    key: 'q',
                    description: 'Recherche sur le titre et la source (insensible à la casse et aux accents).',
                    required: false,
                    schema: ['type' => 'string'],
                ),
            ],
        ),
        new Get(
            uriTemplate: '/admin/texts/{id}',
            provider: AdminTextProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
    ],
)]
final class AdminTextResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $title = null,
        public readonly ?string $source = null,
        public readonly ?string $body = null,
        public readonly ?string $hints = null,
        public readonly ?int $attributionCount = null,
    ) {
    }
}
