<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use App\Chain\Application\Dto\AdminInscriptionView;
use App\Chain\Infrastructure\ApiPlatform\State\Provider\AdminInscriptionProvider;

/**
 * Admin resource for reading the inscriptions of an edition (F16-T03,
 * US-16.2): who is registered on which slot, with the display name and the
 * e-mail address of the priant. The e-mails are served here only (T17,
 * RG-PL-4): the public planning never leaks them.
 *
 * - GET /api/admin/inscriptions?edition={uuid}  → the inscriptions of the
 *   edition, ordered by slot rank then display name
 * - GET /api/admin/inscriptions?edition={uuid}&slot={uuid} → narrowed to
 *   one slot
 *
 * Reserved to organizers: AdminAccessListener answers 401 to visitors and
 * 403 to authenticated non-organizers on /api/admin/*.
 */
#[ApiResource(
    shortName: 'AdminInscription',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/inscriptions',
            provider: AdminInscriptionProvider::class,
            security: 'is_granted("ROLE_ORGANIZER")',
            parameters: [
                'edition' => new QueryParameter(
                    key: 'edition',
                    description: 'Identifiant de l’édition dont on lit les inscriptions.',
                    required: true,
                    schema: ['type' => 'string', 'format' => 'uuid'],
                ),
                'slot' => new QueryParameter(
                    key: 'slot',
                    description: 'Identifiant du créneau : restreint la liste à ce créneau.',
                    required: false,
                    schema: ['type' => 'string', 'format' => 'uuid'],
                ),
            ],
        ),
    ],
)]
final class AdminInscriptionResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $displayName = null,
        public readonly ?string $email = null,
        public readonly ?string $origin = null,
        public readonly ?string $slotId = null,
        public readonly ?int $slotRank = null,
        public readonly ?string $dayLabel = null,
        public readonly ?string $slotLabel = null,
        public readonly ?string $startsAt = null,
        public readonly ?string $endsAt = null,
    ) {
    }

    public static function fromView(AdminInscriptionView $view): self
    {
        return new self(
            id: $view->inscriptionId,
            displayName: $view->displayName,
            email: $view->email,
            origin: $view->origin,
            slotId: $view->slotId,
            slotRank: $view->slotRank,
            dayLabel: $view->dayLabel,
            slotLabel: $view->slotLabel,
            startsAt: $view->startsAtUtc,
            endsAt: $view->endsAtUtc,
        );
    }
}
