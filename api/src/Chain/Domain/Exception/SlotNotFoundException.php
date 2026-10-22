<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a slot cannot be found within an edition.
 */
#[ErrorResource(openapi: false)]
final class SlotNotFoundException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::NOT_FOUND;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'slot.not-found',
            errorDetail: 'Créneau introuvable.',
            errorCode: self::ERROR_CODE,
        );
    }
}
