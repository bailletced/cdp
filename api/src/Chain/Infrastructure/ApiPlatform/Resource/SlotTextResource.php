<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\SlotTextProvider;

/**
 * Reading page of a slot (F12-T02, US-12.1, US-12.2).
 *
 * - GET /api/slots/{id}/text → the schedule of the slot and its full text,
 *   or `text: null` while none is attributed (RG-TX-2)
 *
 * Reserved to the priants registered on the slot — through their session,
 * past editions included, or the pass of their device — and to the
 * organizers (RG-TX-1). Anyone else gets a 404, as for an unknown slot:
 * the existence of a text is not a secret, its content is.
 */
#[ApiResource(
    shortName: 'SlotText',
    operations: [
        new Get(
            uriTemplate: '/slots/{id}/text',
            // RG-TX-2: a slot without text answers `text: null`, never omits it
            normalizationContext: ['skip_null_values' => false],
            provider: SlotTextProvider::class,
        ),
    ],
)]
final class SlotTextResource
{
    /**
     * @param array{title: string, source: string, body: string, hints: string|null}|null $text
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $editionId = null,
        public readonly ?string $editionTitle = null,
        public readonly ?string $startsAt = null,
        public readonly ?string $endsAt = null,
        public readonly ?string $label = null,
        public readonly ?string $dayLabel = null,
        public readonly ?array $text = null,
    ) {
    }
}
