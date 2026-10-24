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
 * public (RG-PL-1). The participants — names and pointages — are null
 * unless the viewer is an inscrit of the edition or an organizer (RG-PL-4,
 * RG-LV-2); `mine` marks the viewer's slots (F10-T08). E-mails and priant
 * identities are never exposed (RG-EP-2).
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
     * @param array<array{id: string, rank: int, startsAt: string, endsAt: string, label: string, partOfDay: string, state: string, textTitle: string|null, textSource: string|null, registrationCount: int, occupied: bool, participants: list<array{name: string, prayed: bool}>|null, mine: bool}>|null $slots
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?array $slots = null,
    ) {
    }
}
