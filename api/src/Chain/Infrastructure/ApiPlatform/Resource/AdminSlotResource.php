<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\AdminSlotProcessor;

/**
 * Admin resource for slot text attribution (F07-T03).
 *
 * - PUT /api/admin/slots/{id}/text → assign or withdraw a text on a slot
 *
 * Reserved to organizers: AdminAccessListener answers 401 to visitors and 403
 * to authenticated non-organizers on /api/admin/*.
 */
#[ApiResource(
    shortName: 'AdminSlot',
    operations: [
        new Put(
            uriTemplate: '/admin/slots/{id}/text',
            processor: AdminSlotProcessor::class,
            read: false,
            security: 'is_granted("ROLE_ORGANIZER")',
        ),
    ],
)]
final class AdminSlotResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly ?string $id = null,
        public readonly ?string $textId = null,
    ) {
    }
}
