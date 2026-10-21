<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

/**
 * Thrown on optimistic-lock conflict: the edition was modified by someone else
 * (converted to a 409 by the API layer).
 */
final class EditionVersionConflictException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('L’édition a été modifiée par quelqu’un d’autre. Veuillez recharger.');
    }
}
