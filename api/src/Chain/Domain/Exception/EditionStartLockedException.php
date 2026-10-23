<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when the start time of an edition is changed after the first
 * inscription exists (RG-ED-3): the slots are already published and taken.
 */
#[ErrorResource(openapi: false)]
final class EditionStartLockedException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::CONFLICT;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'edition.start-locked',
            errorDetail: 'L’heure de début n’est plus modifiable dès la première inscription (RG-ED-3).',
            errorCode: self::ERROR_CODE,
        );
    }
}
