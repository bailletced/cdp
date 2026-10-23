<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;

/**
 * Fake command bus: throws the given exception on dispatch.
 */
final class ThrowingCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly \Throwable $exception,
    ) {
    }

    #[\Override]
    public function dispatch(CommandInterface $command): mixed
    {
        throw $this->exception;
    }
}
