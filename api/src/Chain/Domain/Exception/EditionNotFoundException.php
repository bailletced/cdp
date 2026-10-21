<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when an edition cannot be found.
 */
#[ErrorResource(openapi: false)]
final class EditionNotFoundException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::NOT_FOUND;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'edition.not-found',
            errorDetail: 'Édition introuvable.',
            errorCode: self::ERROR_CODE,
        );
    }
}
