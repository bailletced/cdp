<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ApiPlatform\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Exception pour les conflits (unicité, verrou optimiste).
 * Retourne un code HTTP 409.
 */
final class ConflictException extends ConflictHttpException
{
    public function __construct(string $message = 'Conflit : la ressource existe déjà ou a été modifiée.', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
