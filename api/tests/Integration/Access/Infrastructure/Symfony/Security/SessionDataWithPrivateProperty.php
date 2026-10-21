<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Symfony\Security;

/**
 * Fixture for SessionIntegrationTest: a class with a private property, so
 * that serialize() produces a string containing null bytes, like the
 * serialized security token stored in an authenticated session.
 */
final class SessionDataWithPrivateProperty
{
    public function __construct(private readonly string $value)
    {
    }
}
