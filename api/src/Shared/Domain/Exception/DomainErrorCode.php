<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

enum DomainErrorCode
{
    case NOT_FOUND;
    case DELETED;
    case CONFLICT;
    case PRECONDITION_FAILED;
    case NOT_AUTHORIZED;
    case NOT_AUTHENTICATED;
    case INVALID_INPUT;
    case BAD_REQUEST;
}
