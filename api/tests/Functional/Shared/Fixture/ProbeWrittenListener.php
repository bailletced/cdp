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
        $this->recorder->transactionWasActive[] = $this->entityManager->getConnection()->isTransactionActive();
    }
}
