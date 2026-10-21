<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

/**
 * Admin resource for managing organizers (F04-T02).
 * Reserved to ROLE_ORGANIZER via access_control on /api/admin/*.
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
final class AdminOrganizer
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
