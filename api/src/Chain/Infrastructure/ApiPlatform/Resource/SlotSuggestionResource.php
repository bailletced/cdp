<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\SlotSuggestionProvider;

/**
 * Draw of an empty slot (F13-T03, US-13.2, US-13.3).
 *
 * - GET /api/editions/{id}/slot-suggestion?near=&exclude= → an empty slot
 *   still to come, with its Paris label and the title of its text; 404 when
 *   the chain is complete, or for a draft or unknown edition
 *
 * No authentication required: « Prendre un créneau vide » is offered to
 * every visitor. The identifier is the edition id; no priant is exposed.
 */
#[ApiResource(
    shortName: 'SlotSuggestion',
    operations: [
        new Get(
            uriTemplate: '/editions/{id}/slot-suggestion',
            provider: SlotSuggestionProvider::class,
            // A slot without text answers `textTitle: null`, never omits it
            normalizationContext: ['skip_null_values' => false],
            parameters: [
                'near' => new QueryParameter(
                    key: 'near',
                    description: 'Créneau près duquel chercher : le suivant puis le précédent, s’ils sont vides.',
                    required: false,
                    schema: ['type' => 'string'],
                ),
                'exclude' => new QueryParameter(
                    key: 'exclude',
                    description: 'Créneaux déjà proposés, séparés par des virgules : écartés tant qu’un autre reste.',
                    required: false,
                    schema: ['type' => 'string'],
                ),
            ],
        ),
    ],
)]
final class SlotSuggestionResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $slotId = null,
        public readonly ?string $startsAt = null,
        public readonly ?string $endsAt = null,
        public readonly ?string $label = null,
        public readonly ?string $textTitle = null,
        public readonly ?string $textSource = null,
    ) {
    }
}
