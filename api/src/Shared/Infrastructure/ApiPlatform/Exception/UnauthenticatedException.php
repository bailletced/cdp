<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\Exception;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Exception for API endpoints that require an authenticated session.
 * Returns an HTTP 401 (the visitor is not authenticated).
 */
final class UnauthenticatedException extends UnauthorizedHttpException
{
    public function __construct(string $message = 'Authentification requise.', ?\Throwable $previous = null)
    {
        parent::__construct('', $message, $previous);
    }
}
