<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Payload;

use ApiPlatform\Metadata\ApiProperty;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Shared\Infrastructure\Symfony\Validator\AssertValueObject;

/**
 * Input payload of PATCH /api/me (F14-T04): the new display name. Its bounds
 * reference the value object, the single source of truth.
 */
final class RenameMePayload
{
    public function __construct(
        #[AssertValueObject(DisplayName::class)]
        #[ApiProperty(schema: [
            'type' => ['string', 'null'],
            'minLength' => DisplayName::MIN_LENGTH,
            'maxLength' => DisplayName::MAX_LENGTH,
        ])]
        public readonly ?string $displayName = null,
    ) {
    }
}
