<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

/**
 * Minimal representation of the currently authenticated account (F02-T16).
 * Exposed as GET /api/me; visitors get a 401 from the provider.
 */
#[ApiResource(
    shortName: 'Me',
    operations: [
        new Get(
            uriTemplate: '/me',
            provider: MeProvider::class,
        ),
    ],
)]
final class Me
{
    public function __construct(
        public readonly string $email,
        public readonly bool $isOrganizer,
    ) {
    }
}
