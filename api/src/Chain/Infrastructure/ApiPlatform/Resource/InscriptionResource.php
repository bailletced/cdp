<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Chain\Infrastructure\ApiPlatform\Payload\RegisterInscriptionsPayload;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\RegisterInscriptionsProcessor;

/**
 * Public resource for the registration of a priant on one or more slots
 * (F09-T10).
 *
 * - POST /api/inscriptions → 201 with the created inscriptions, 422 on an
 *   invalid payload (one violation per field), 404 on an unknown slot,
 *   409 when the priant is already registered on a slot (RG-IN-3), when the
 *   edition is closed for inscriptions or when the slot is past (F09-T08).
 *
 * No authentication required: the registration form is public (the device
 * pass cookie is granted in F10). SameOriginListener covers the unsafe method.
 *
 * F13-T04: `suggestion` is an empty slot next to the latest one taken, or
 * null once the chain is complete (US-13.3).
 */
#[ApiResource(
    shortName: 'Inscription',
    operations: [
        new Post(
            uriTemplate: '/inscriptions',
            input: RegisterInscriptionsPayload::class,
            processor: RegisterInscriptionsProcessor::class,
            // A complete chain answers `suggestion: null`, never omits it
            normalizationContext: ['skip_null_values' => false],
        ),
    ],
)]
final class InscriptionResource
{
    /**
     * @param array<array{id: string, slotId: string, editionId: string}>                                                                  $inscriptions
     * @param array{slotId: string, startsAt: string, endsAt: string, label: string, textTitle: string|null, textSource: string|null}|null $suggestion
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $priantId = null,
        public readonly ?string $displayName = null,
        #[ApiProperty(schema: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'slotId' => ['type' => 'string', 'format' => 'uuid'],
                    'editionId' => ['type' => 'string', 'format' => 'uuid'],
                ],
            ],
        ])]
        public readonly array $inscriptions = [],
        #[ApiProperty(schema: [
            'type' => ['object', 'null'],
            'properties' => [
                'slotId' => ['type' => 'string', 'format' => 'uuid'],
                'startsAt' => ['type' => 'string', 'format' => 'date-time'],
                'endsAt' => ['type' => 'string', 'format' => 'date-time'],
                'label' => ['type' => 'string'],
                'textTitle' => ['type' => ['string', 'null']],
                'textSource' => ['type' => ['string', 'null']],
            ],
        ])]
        public readonly ?array $suggestion = null,
    ) {
    }
}
