<?php

declare(strict_types=1);

namespace App\Shared\Application\Dto;

/**
 * The public profile of a priant, exposed to the priant himself through
 * GET /api/me (F09-T14, RG-IN-7): the display name to pre-fill and whether
 * the consent was already given (the consent checkbox is then hidden).
 */
final readonly class PriantProfile
{
    public function __construct(
        public string $displayName,
        public bool $consentGiven,
    ) {
    }
}
