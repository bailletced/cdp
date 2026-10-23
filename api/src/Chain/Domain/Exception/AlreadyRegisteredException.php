<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a priant already has an inscription on the slot (RG-IN-3),
 * whether detected by the repository or by the unique index on a concurrent
 * request.
 */
#[ErrorResource(openapi: false)]
final class AlreadyRegisteredException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::CONFLICT;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'inscription.already-registered',
            errorDetail: 'Vous êtes déjà inscrit sur ce créneau.',
            errorCode: self::ERROR_CODE,
        );
    }
}
