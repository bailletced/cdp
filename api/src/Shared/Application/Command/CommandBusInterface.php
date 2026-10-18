<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

interface CommandBusInterface
{
    /**
     * Dispatch a command on the synchronous command bus (wrapped in a transaction)
     * and return the handler's result.
     */
    public function dispatch(CommandInterface $command): mixed;
}
