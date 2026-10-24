<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionResult;
use App\Chain\Application\DistributeTexts;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Domain\Entity\Text;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionTitle;
use App\Chain\Domain\ValueObject\Presentation;
use App\Chain\Domain\ValueObject\TextId;
use App\Shared\Application\Command\CommandBusInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * F05-T17: Load edition fixtures via the command bus.
 *
 * F07-T11: the open edition carries the library texts, in the order of the
 * demonstration library (run app:chain:load-text-fixtures first); the draft
 * edition stays without texts, to try the attribution on it.
 */
#[AsCommand(name: 'app:chain:load-edition-fixtures', description: 'Charge les fixtures éditions (F05-T17)')]
final class LoadEditionFixturesCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ClockInterface $clock,
        private TextRepositoryInterface $texts,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->clock->now();
        $parisTz = new \DateTimeZone('Europe/Paris');
        $nowParis = $now->setTimezone($parisTz);

        // 1. Open edition: starts in about two hours, opened yesterday (both on
        // a half hour, RG-ED-2)
        $startsAtParis = self::halfHourFloor($nowParis->modify('+2 hours'));
        $opensAtParis = self::halfHourFloor($nowParis->modify('-1 day'));

        $result1 = $this->commandBus->dispatch(new CreateEdition(
            new EditionTitle('24 heures de prière — édition en cours'),
            new Presentation('Édition de démonstration, ouverte aux inscriptions.'),
            $startsAtParis,
            $opensAtParis,
        ));

        if (!$result1 instanceof CreateEditionResult) {
            throw new \LogicException(\sprintf('Expected the command bus to return an %s, got %s.', CreateEditionResult::class, get_debug_type($result1)));
        }

        $textIds = $this->libraryInFixtureOrder();
        if ([] === $textIds) {
            $output->writeln('<comment>Bibliothèque vide : l’édition ouverte reste sans texte.</comment>');
        } else {
            $this->commandBus->dispatch(new DistributeTexts($result1->editionId, $textIds, 1));
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

    private static function halfHourFloor(\DateTimeImmutable $time): \DateTimeImmutable
    {
        return $time->setTime((int) $time->format('H'), (int) $time->format('i') >= 30 ? 30 : 0);
    }

    /**
     * The library texts, the demonstration ones first in their order, then
     * any other text.
     *
     * @return list<TextId>
     */
    private function libraryInFixtureOrder(): array
    {
        $rank = array_flip(array_map(static fn (array $text): string => $text['title'], TextFixtures::all()));
        $texts = $this->texts->findAll();

        usort($texts, static fn (Text $a, Text $b): int => ($rank[$a->getTitle()->value] ?? \PHP_INT_MAX) <=> ($rank[$b->getTitle()->value] ?? \PHP_INT_MAX));

        return array_map(static fn (Text $text): TextId => $text->getId(), $texts);
    }
}
