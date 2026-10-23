<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\PublicEditionSlotsProvider;

/**
 * Public resource for the planning grid of an edition (F08-T04, US-08.2).
 *
 * - GET /api/editions/{id}/slots → the 48 slots of a public edition, or 404
 *   when the edition is unknown or not scheduled (draft)
 *
 * No authentication required: this feeds the public planning page. The
 * identifier is the edition id. The registration count of each slot is
 * public (RG-PL-1); priant identities and names are never exposed
 * (RG-PL-4). The "you" flag is added in F10.
 */
#[ApiResource(
    shortName: 'PublicEditionSlots',
    operations: [
        new Get(
            uriTemplate: '/editions/{id}/slots',
            provider: PublicEditionSlotsProvider::class,
        ),
    ],
)]
final class PublicEditionSlotsResource
{
    /**
     * @param array<array{id: string, rank: int, startsAt: string, endsAt: string, label: string, partOfDay: string, state: string, textTitle: string|null, textSource: string|null, registrationCount: int, occupied: bool}>|null $slots
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?array $slots = null,
    ) {
    }
}
