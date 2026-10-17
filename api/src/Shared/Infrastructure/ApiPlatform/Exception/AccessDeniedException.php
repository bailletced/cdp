<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Exception pour les erreurs d'accès refusé.
 * Retourne un code HTTP 403.
 */
final class AccessDeniedException extends AccessDeniedHttpException
{
    public function __construct(string $message = 'Accès refusé.', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
