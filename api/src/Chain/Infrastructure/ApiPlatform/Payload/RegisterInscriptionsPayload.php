<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\ApiPlatform\Payload;

use ApiPlatform\Metadata\ApiProperty;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Symfony\Validator\AssertValueObject;

/**
 * Input payload of POST /api/inscriptions (F09-T10).
 *
 * - slotIds: one or more slots of a single edition (RG-IN-4);
 * - displayName / email: identify the priant (RG-IN-2);
 * - consent: mandatory off-session, or when the priant never consented (RG-IN-5);
 * - website: honeypot field (§6.7) — when filled, the response looks like a
 *   success but nothing is done. The front keeps it out of the accessibility
 *   tree.
 *
 * The bounds are published in the OpenAPI schema (they bound the front's
 * Standard Schema validation, F09-T16) and reference the value object
 * constants, which stay the single source of truth. The processor turns
 * invalid values into 422 violations, one per field.
 */
final class RegisterInscriptionsPayload
{
    /**
     * @param ?list<string> $slotIds
     */
    public function __construct(
        #[ApiProperty(schema: [
            'type' => ['array', 'null'],
            'items' => ['type' => 'string', 'format' => 'uuid'],
            'minItems' => 1,
            'uniqueItems' => true,
        ])]
        public readonly ?array $slotIds = null,
        #[AssertValueObject(DisplayName::class)]
        #[ApiProperty(schema: [
            'type' => ['string', 'null'],
            'minLength' => DisplayName::MIN_LENGTH,
            'maxLength' => DisplayName::MAX_LENGTH,
        ])]
        public readonly ?string $displayName = null,
        #[AssertValueObject(Email::class)]
        #[ApiProperty(schema: [
            'type' => ['string', 'null'],
            'minLength' => Email::MIN_LENGTH,
            'maxLength' => Email::MAX_LENGTH,
            'format' => 'email',
        ])]
        public readonly ?string $email = null,
        #[ApiProperty(schema: ['type' => ['boolean', 'null']])]
        public readonly ?bool $consent = null,
        #[ApiProperty(schema: ['type' => ['string', 'null']])]
        public readonly ?string $website = null,
    ) {
    }
}
