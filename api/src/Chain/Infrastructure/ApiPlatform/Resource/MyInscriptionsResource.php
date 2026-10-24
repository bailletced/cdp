<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\MyInscriptionsProvider;

/**
 * « Mon espace » (F14-T02, US-14.1).
 *
 * - GET /api/me/inscriptions?edition= → the current name of the priant and
 *   its slots of an edition — the public one by default — with their state
 *   and the actions still possible
 *
 * Reserved to the verified priant: a visitor or a device pass gets a 401
 * (RG-IN-6), an account without priant a 403, an unknown edition — or no
 * public edition — a 404.
 */
#[ApiResource(
    shortName: 'MyInscriptions',
    operations: [
        new Get(
            uriTemplate: '/me/inscriptions',
            provider: MyInscriptionsProvider::class,
            // A slot without text answers `textTitle: null`, never omits it
            normalizationContext: ['skip_null_values' => false],
            parameters: [
                'edition' => new QueryParameter(
                    key: 'edition',
                    description: 'Édition à afficher ; l’édition publique par défaut.',
                    required: false,
                    schema: ['type' => 'string'],
                ),
            ],
        ),
    ],
)]
final class MyInscriptionsResource
{
    /**
     * @param list<array{id: string, slotId: string, startsAt: string, endsAt: string, label: string, dayLabel: string, textTitle: string|null, textSource: string|null, state: string, actions: list<string>}> $inscriptions
     */
    public function __construct(
        #[ApiProperty(identifier: false)]
        public readonly ?string $editionId = null,
        public readonly ?string $editionTitle = null,
        public readonly ?string $displayName = null,
        #[ApiProperty(schema: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'slotId' => ['type' => 'string', 'format' => 'uuid'],
                    'startsAt' => ['type' => 'string', 'format' => 'date-time'],
                    'endsAt' => ['type' => 'string', 'format' => 'date-time'],
                    'label' => ['type' => 'string'],
                    'dayLabel' => ['type' => 'string'],
                    'textTitle' => ['type' => ['string', 'null']],
                    'textSource' => ['type' => ['string', 'null']],
                    'state' => ['type' => 'string', 'enum' => ['past', 'current', 'future']],
                    'actions' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['cancel']]],
                ],
            ],
        ])]
        public readonly array $inscriptions = [],
    ) {
    }
}
