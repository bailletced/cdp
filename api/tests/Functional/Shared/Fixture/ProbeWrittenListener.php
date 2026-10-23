<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Fixture;

use App\Shared\Application\Event\AsEventListener;
use Doctrine\ORM\EntityManagerInterface;

#[AsEventListener]
final readonly class ProbeWrittenListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProbeRecorder $recorder,
    ) {
    }

    public function __invoke(ProbeWrittenEvent $event): void
    {
        $connection = $this->entityManager->getConnection();

        // The event bus wraps the listener in its own transaction
        // (doctrine_transaction): one is active here, but it is not the
        // command's — the SET LOCAL marker set by the command handler is
        // gone once that transaction has committed.
        $this->recorder->transactionWasActive[] = $connection->isTransactionActive();
        $this->recorder->insideCommandTransaction[] = '1' === $connection->fetchOne("SELECT current_setting('app.probe_command_tx', true)");
    }
}
