<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\CreateText;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * F06-T15: load the demonstration library (48 public-domain texts) via the
 * command bus, so each text goes through the same sanitizing as a text
 * typed by an organizer. A library that already holds texts is left
 * untouched: the command can run on every `make fixtures`.
 */
#[AsCommand(name: 'app:chain:load-text-fixtures', description: 'Charge la bibliothèque de textes de démonstration (F06-T15)')]
final class LoadTextFixturesCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TextRepositoryInterface $texts,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ([] !== $this->texts->findAll()) {
            $output->writeln('<comment>La bibliothèque contient déjà des textes : aucun texte ajouté.</comment>');

            return Command::SUCCESS;
        }

        $fixtures = TextFixtures::all();

        foreach ($fixtures as $text) {
            $this->commandBus->dispatch(new CreateText(
                new TextTitle($text['title']),
                new TextSource($text['source']),
                $text['body'],
                $text['hints'],
            ));
        }

        $output->writeln(\sprintf('<info>%d textes ajoutés à la bibliothèque.</info>', \count($fixtures)));

        return Command::SUCCESS;
    }
}
