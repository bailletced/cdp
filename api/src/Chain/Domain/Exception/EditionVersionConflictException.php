<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown on optimistic-lock conflict: the edition was modified by someone else.
 */
#[ErrorResource(openapi: false)]
final class EditionVersionConflictException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::CONFLICT;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'edition.version-conflict',
            errorDetail: 'L’édition a été modifiée par quelqu’un d’autre. Veuillez recharger.',
            errorCode: self::ERROR_CODE,
        );
    }
}
