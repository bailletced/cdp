<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;

/**
 * Fake command bus: records the dispatched command and returns a canned result.
 */
final class FakeCommandBus implements CommandBusInterface
{
    public ?CommandInterface $dispatched = null;

    public function __construct(
        private readonly mixed $result = null,
    ) {
    }

    #[\Override]
    public function dispatch(CommandInterface $command): mixed
    {
        $this->dispatched = $command;

        return $this->result;
    }
}
