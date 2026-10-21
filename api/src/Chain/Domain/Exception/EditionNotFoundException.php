<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

/**
 * Thrown when an edition cannot be found (converted to a 404 by the API layer).
 */
final class EditionNotFoundException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Édition introuvable.');
    }
}
