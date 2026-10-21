<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Access\Infrastructure\ApiPlatform\State\Processor\AdminOrganizerProcessor;
use App\Access\Infrastructure\ApiPlatform\State\Provider\AdminOrganizerProvider;

/**
 * Admin resource for managing organizers (F04-T02).
 * Reserved to organizers: access_control on /api/admin/* (security.yaml)
 * answers 401 to visitors, AdminAccessListener answers 403 to non-organizers.
 *
 * - GET  /api/admin/organizers        → list all organizers
 * - POST /api/admin/organizers        → add an organizer by email
 * - DELETE /api/admin/organizers/{id} → remove an organizer
 */
#[ApiResource(
    shortName: 'AdminOrganizer',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/organizers',
            provider: AdminOrganizerProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Post(
            uriTemplate: '/admin/organizers',
            processor: AdminOrganizerProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
        new Delete(
            uriTemplate: '/admin/organizers/{id}',
            provider: AdminOrganizerProvider::class,
            processor: AdminOrganizerProcessor::class,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
    ],
)]
final class AdminOrganizerResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $email = null,
        public readonly ?string $addedByEmail = null,
        public readonly ?string $addedAt = null,
    ) {
    }
}
