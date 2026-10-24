<?php

declare(strict_types=1);

namespace App\Chain\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use App\Shared\Domain\Exception\DomainErrorCode;
use App\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a priant tries to cancel an inscription whose slot has
 * started (RG-EP-1): the priant is expected, the slot is no longer free.
 */
#[ErrorResource(openapi: false)]
final class SlotAlreadyStartedException extends DomainException
{
    public const DomainErrorCode ERROR_CODE = DomainErrorCode::NOT_AUTHORIZED;

    public function __construct()
    {
        parent::__construct(
            errorTitle: 'slot.already-started',
            errorDetail: 'Ce créneau a déjà commencé : la désinscription n’est plus possible.',
            errorCode: self::ERROR_CODE,
        );
    }
}
