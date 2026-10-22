<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Command\CommandBusInterface;
use App\Tests\Functional\Shared\Fixture\ProbeRecorder;
use App\Tests\Functional\Shared\Fixture\WriteProbeCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class MessengerEventBusTest extends KernelTestCase
{
    use InteractsWithMessenger;

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

        // The event never reaches the outbox: the deferred dispatch is dropped
        // when the command transaction rolls back.
        $this->transport('async_transactional')->queue()->assertEmpty();
        self::assertSame([], $this->recorder->transactionWasActive);
    }

    public function testAListenerIsCalledOnlyOnceTheTransactionHasBeenCommitted(): void
    {
        $this->commandBus->dispatch(new WriteProbeCommand('published'));

        // Domain events are routed to the async_transactional outbox: consume
        // them like the worker would. The listener must then run outside of
        // the command transaction (no active transaction in the worker).
        $this->transport('async_transactional')->processOrFail();

        self::assertSame([false], $this->recorder->transactionWasActive);
    }
}
