<?php

declare(strict_types=1);

namespace App\Access\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when removing the last remaining organizer (RG: at least one organizer
 * must always remain). A business rule refusal: surfaces as a 400, NOT a 422
 * (422 is reserved for validation violations with a violations[] payload).
 */
#[ErrorResource(openapi: false)]
final class LastOrganizerException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::BAD_REQUEST;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'last-organizer',
            errorDetail: 'Il doit toujours rester au moins un organisateur.',
            errorCode: self::ERROR_CODE,
        );
    }
}
