<?php

declare(strict_types=1);

namespace App\Shared\Application\Dto;

/**
 * A personal (passwordless) login link issued for an account.
 */
final readonly class PersonalLink
{
    public function __construct(
        public string $url,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
