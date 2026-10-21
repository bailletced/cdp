<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Console;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionResult;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Shared\Application\Command\CommandBusInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * F05-T17: Load edition fixtures via the command bus.
 */
#[AsCommand(name: 'app:chain:load-edition-fixtures', description: 'Charge les fixtures éditions (F05-T17)')]
final class LoadEditionFixturesCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->clock->now();
        $parisTz = new \DateTimeZone('Europe/Paris');
        $nowParis = $now->setTimezone($parisTz);

        // 1. Open edition: starts soon, opened yesterday
        $startsAtParis = $nowParis->modify('+2 hours')->setTime((int) $nowParis->format('H'), (int) $nowParis->format('i') >= 30 ? 30 : 0);
        $opensAtParis = $nowParis->modify('-1 day');

        $result1 = $this->commandBus->dispatch(new CreateEdition(
            new EditionTitle('24 heures de prière — édition en cours'),
            new Presentation('Édition de démonstration, ouverte aux inscriptions.'),
            $startsAtParis,
            $opensAtParis,
        ));

        if (!$result1 instanceof CreateEditionResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', CreateEditionResult::class, get_debug_type($result1)));
        }

        $this->commandBus->dispatch(new ScheduleEdition($result1->editionId));

        $output->writeln(\sprintf('<info>Édition ouverte créée : %s</info>', (string) $result1->editionId));

        // 2. Draft edition: starts in 2 weeks
        $startsAtParis2 = $nowParis->modify('+14 days')->setTime(20, 0);
        $opensAtParis2 = $nowParis->modify('+7 days')->setTime(20, 0);

        $result2 = $this->commandBus->dispatch(new CreateEdition(
            new EditionTitle('24 heures de prière — prochaine édition'),
            new Presentation('Édition de démonstration, en brouillon.'),
            $startsAtParis2,
            $opensAtParis2,
        ));

        if (!$result2 instanceof CreateEditionResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', CreateEditionResult::class, get_debug_type($result2)));
        }

        $output->writeln(\sprintf('<info>Édition brouillon créée : %s</info>', (string) $result2->editionId));

        return Command::SUCCESS;
    }
}
