<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

use App\Shared\Application\Command\AsCommandHandler;
use App\Shared\Application\Event\EventBusInterface;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommandHandler]
final readonly class WriteProbeCommandHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(WriteProbeCommand $command): string
    {
        $this->entityManager->getConnection()->executeStatement('INSERT INTO command_bus_probe (value) VALUES (?)', [$command->value]);
        // Transaction-scoped marker (SET LOCAL): visible only inside this
        // command transaction. The listener checks it to prove it does NOT
        // run in the command's transaction (transactional outbox).
        $this->entityManager->getConnection()->executeStatement("SET LOCAL app.probe_command_tx = '1'");
        $this->eventBus->dispatch(new ProbeWrittenEvent($command->value));

        if ($command->fail) {
            throw new \DomainException('The handler failed.');
        }

        return $command->value;
    }
}
