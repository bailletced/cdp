<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Console;

use App\Access\Domain\Account;
use App\Access\Domain\Organizer;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

/**
 * Console command to create an organizer account.
 * Idempotent: if the account already exists, it will not create a duplicate organizer.
 */
#[AsCommand(
    name: 'app:organizer:create',
    description: 'Creates an organizer account with the given email address.',
)]
final class CreateOrganizerCommand extends Command
{
    private const string ARGUMENT_EMAIL = 'email';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private OrganizerRepositoryInterface $organizerRepository,
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

        try {
            $email = new Email($emailString);
        } catch (\InvalidArgumentException $e) {
            $io->error(\sprintf('Invalid email address: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // Check if account already exists
        $existingAccount = $this->accountRepository->findByEmail($email);

        if ($existingAccount !== null) {
            // Check if already an organizer
            if ($this->organizerRepository->isOrganizer($existingAccount->getId()->value)) {
                $io->success(\sprintf('Account with email "%s" is already an organizer.', $emailString));
                return Command::SUCCESS;
            }

            // Account exists but is not an organizer, make them an organizer
            $accountId = $existingAccount->getId()->value;
        } else {
            // Create new account
            $account = new Account(
                new AccountId(),
                $email,
                1,
                null,
            );
            $this->accountRepository->save($account);
            $accountId = $account->getId()->value;
            $io->text(\sprintf('Created new account with email "%s".', $emailString));
        }

        // Check if already an organizer (in case of concurrent creation)
        if ($this->organizerRepository->isOrganizer($accountId)) {
            $io->success(\sprintf('Account with email "%s" is already an organizer.', $emailString));
            return Command::SUCCESS;
        }

        // Create organizer
        $organizer = new Organizer(
            $accountId,
            $accountId, // Added by self for the first organizer
        );
        $this->organizerRepository->save($organizer);

        $io->success(\sprintf('Successfully created organizer with email "%s".', $emailString));
        
        return Command::SUCCESS;
    }
}
