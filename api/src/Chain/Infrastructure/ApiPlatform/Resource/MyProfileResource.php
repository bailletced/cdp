<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use App\Chain\Infrastructure\ApiPlatform\Payload\RenameMePayload;
use App\Chain\Infrastructure\ApiPlatform\State\Processor\RenameMeProcessor;

/**
 * The display name of the verified priant (F14-T04, US-14.2).
 *
 * - PATCH /api/me (merge-patch) → `displayName`, 422 when empty or too long
 *
 * GET /api/me belongs to Access (the account); the name belongs to the
 * priant, hence to Chain. A visitor or a device pass gets a 401 (RG-IN-6),
 * an account without priant a 403. SameOriginListener covers the unsafe
 * method.
 */
#[ApiResource(
    shortName: 'MyProfile',
    operations: [
        new Patch(
            uriTemplate: '/me',
            input: RenameMePayload::class,
            processor: RenameMeProcessor::class,
            read: false,
        ),
    ],
)]
final class MyProfileResource
{
    public function __construct(
        public readonly ?string $displayName = null,
    ) {
    }
}
