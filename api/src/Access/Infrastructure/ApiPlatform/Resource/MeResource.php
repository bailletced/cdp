<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Access\Infrastructure\ApiPlatform\State\Provider\MeProvider;

/**
 * Minimal representation of the currently authenticated account (F02-T16),
 * enriched with the linked priant profile (F09-T14, RG-IN-7): the display
 * name to pre-fill and whether the consent was already given. Exposed as
 * GET /api/me; visitors get a 401 from the provider.
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
final class MeResource
{
    public function __construct(
        public readonly string $email,
        public readonly bool $isOrganizer,
        public readonly ?string $displayName = null,
        public readonly bool $consentGiven = false,
    ) {
    }
}
