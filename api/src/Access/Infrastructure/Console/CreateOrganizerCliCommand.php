<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Console;

use App\Access\Application\AddOrganizer;
use App\Access\Application\AddOrganizerResult;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console adapter for the AddOrganizer use case.
 * Thin controller: it parses the input, dispatches the application command on
 * the command bus and prints the outcome. The logic lives in the handler.
 */
#[AsCommand(
    name: 'app:organizer:create',
    description: 'Creates an organizer account with the given email address.',
)]
final class CreateOrganizerCliCommand extends Command
{
    private const string ARGUMENT_EMAIL = 'email';

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_EMAIL,
            InputArgument::REQUIRED,
            'The email address of the organizer to create.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $emailString = $input->getArgument(self::ARGUMENT_EMAIL);

        if (!\is_string($emailString)) {
            $io->error('The email argument must be a string.');

            return Command::INVALID;
        }

        try {
            $email = new Email($emailString);
        } catch (\InvalidArgumentException $e) {
            $io->error(\sprintf('Invalid email address: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $outcome = $this->commandBus->dispatch(new AddOrganizer($email));

        if (!$outcome instanceof AddOrganizerResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', AddOrganizerResult::class, get_debug_type($outcome)));
        }

        if (AddOrganizerResult::AccountCreated === $outcome) {
            $io->text(\sprintf('Created new account with email "%s".', $emailString));
        }

        if (AddOrganizerResult::AlreadyOrganizer === $outcome) {
            $io->success(\sprintf('Account with email "%s" is already an organizer.', $emailString));
        } else {
            $io->success(\sprintf('Successfully created organizer with email "%s".', $emailString));
        }

        return Command::SUCCESS;
    }
}
