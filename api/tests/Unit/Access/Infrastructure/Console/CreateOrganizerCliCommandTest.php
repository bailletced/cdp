<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access\Infrastructure\Console;

use App\Access\Application\AddOrganizer;
use App\Access\Application\AddOrganizerResult;
use App\Access\Infrastructure\Console\CreateOrganizerCliCommand;
use App\Tests\Unit\Shared\Application\Command\Fixture\FakeCommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class CreateOrganizerCliCommandTest extends TestCase
{
    public function testItDispatchesAnAddOrganizerCommandAndReportsTheNewAccount(): void
    {
        $bus = new FakeCommandBus(AddOrganizerResult::AccountCreated);
        $command = new CreateOrganizerCliCommand($bus);

        $output = new BufferedOutput();
        $exitCode = $command->run(new ArrayInput(['email' => 'New@Example.com']), $output);

        self::assertSame(Command::SUCCESS, $exitCode);

        $dispatched = $bus->dispatched;
        if (!$dispatched instanceof AddOrganizer) {
            self::fail(\sprintf('Expected %s to be dispatched, got %s.', AddOrganizer::class, get_debug_type($dispatched)));
        }
        self::assertSame('new@example.com', $dispatched->email->value);

        $display = $output->fetch();
        self::assertStringContainsString('Created new account with email "New@Example.com".', $display);
        self::assertStringContainsString('Successfully created organizer with email "New@Example.com".', $display);
    }

    public function testItReportsAnOrganizerAddedToAnExistingAccount(): void
    {
        $bus = new FakeCommandBus(AddOrganizerResult::OrganizerCreated);
        $command = new CreateOrganizerCliCommand($bus);

        $output = new BufferedOutput();
        $exitCode = $command->run(new ArrayInput(['email' => 'priant@example.com']), $output);

        self::assertSame(Command::SUCCESS, $exitCode);

        $display = $output->fetch();
        self::assertStringContainsString('Successfully created organizer with email "priant@example.com".', $display);
        self::assertStringNotContainsString('Created new account', $display);
    }

    public function testItReportsAnAccountThatIsAlreadyAnOrganizer(): void
    {
        $bus = new FakeCommandBus(AddOrganizerResult::AlreadyOrganizer);
        $command = new CreateOrganizerCliCommand($bus);

        $output = new BufferedOutput();
        $exitCode = $command->run(new ArrayInput(['email' => 'organizer@example.com']), $output);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Account with email "organizer@example.com" is already an organizer.', $output->fetch());
    }

    public function testItFailsOnAnInvalidEmailWithoutDispatching(): void
    {
        $bus = new FakeCommandBus(AddOrganizerResult::AccountCreated);
        $command = new CreateOrganizerCliCommand($bus);

        $output = new BufferedOutput();
        $exitCode = $command->run(new ArrayInput(['email' => 'invalid-email']), $output);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertNull($bus->dispatched);
        self::assertStringContainsString('Invalid email address', $output->fetch());
    }
}
