<?php

declare(strict_types=1);

namespace App\Access\Domain\Exception;

/**
 * Thrown when removing the last remaining organizer (RG: at least one organizer
 * must always remain).
 */
final class LastOrganizerException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Il doit toujours rester au moins un organisateur.');
    }
}
