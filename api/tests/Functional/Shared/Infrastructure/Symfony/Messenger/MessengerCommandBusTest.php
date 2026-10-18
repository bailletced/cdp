<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Command\CommandBusInterface;
use App\Tests\Functional\Shared\Fixture\WriteProbeCommand;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MessengerCommandBusTest extends KernelTestCase
{
    private Connection $connection;
    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->connection->executeStatement('CREATE TABLE IF NOT EXISTS command_bus_probe (value text NOT NULL)');
        $this->connection->executeStatement('TRUNCATE command_bus_probe');
    }

    public function testASuccessfulCommandIsCommitted(): void
    {
        $result = $this->commandBus->dispatch(new WriteProbeCommand('kept'));

        self::assertSame('kept', $result);
        self::assertSame('kept', $this->connection->fetchOne('SELECT value FROM command_bus_probe'));
    }

    public function testAnExceptionRollsBackWhatTheHandlerWrote(): void
    {
        try {
            $this->commandBus->dispatch(new WriteProbeCommand('doomed', fail: true));
            self::fail('The handler exception should reach the caller.');
        } catch (\DomainException $e) {
            self::assertSame('The handler failed.', $e->getMessage());
        }

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT count(*) FROM command_bus_probe'));
    }
}
