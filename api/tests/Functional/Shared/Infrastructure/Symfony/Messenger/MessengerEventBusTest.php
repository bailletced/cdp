<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Command\CommandBusInterface;
use App\Tests\Functional\Shared\Fixture\ProbeRecorder;
use App\Tests\Functional\Shared\Fixture\WriteProbeCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MessengerEventBusTest extends KernelTestCase
{
    private CommandBusInterface $commandBus;
    private ProbeRecorder $recorder;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->recorder = self::getContainer()->get(ProbeRecorder::class);

        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $connection->executeStatement('CREATE TABLE IF NOT EXISTS command_bus_probe (value text NOT NULL)');
    }

    public function testNoEventIsPublishedWhenTheCommandFails(): void
    {
        try {
            $this->commandBus->dispatch(new WriteProbeCommand('doomed', fail: true));
        } catch (\DomainException) {
        }

        self::assertSame([], $this->recorder->transactionWasActive);
    }

    public function testAListenerIsCalledOnlyOnceTheTransactionHasBeenCommitted(): void
    {
        $this->commandBus->dispatch(new WriteProbeCommand('published'));

        self::assertSame([false], $this->recorder->transactionWasActive);
    }
}
