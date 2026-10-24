<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a priant cannot be found.
 */
#[ErrorResource(openapi: false)]
final class PriantNotFoundException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::NOT_FOUND;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'priant.not-found',
            errorDetail: 'Priant introuvable.',
            errorCode: self::ERROR_CODE,
        );
    }
}
