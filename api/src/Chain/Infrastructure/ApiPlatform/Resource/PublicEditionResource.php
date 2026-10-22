<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\PublicEditionProvider;

/**
 * Public resource for the current edition (F08-T02, US-08.1).
 *
 * - GET /api/editions/current → the current public edition, or 404 when none is open
 *
 * No authentication required: this is the public planning page entry point.
 * Cache-Control is set to private, no-store by the global ExceptionSubscriber.
 */
#[ApiResource(
    shortName: 'PublicEdition',
    operations: [
        new Get(
            uriTemplate: '/editions/current',
            provider: PublicEditionProvider::class,
        ),
    ],
)]
final class PublicEditionResource
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $title = null,
        public readonly ?string $presentation = null,
        public readonly ?string $startsAt = null,
        public readonly ?string $opensAt = null,
        public readonly ?string $startsAtLabel = null,
        public readonly ?string $opensAtLabel = null,
        public readonly ?string $status = null,
    ) {
    }
}
