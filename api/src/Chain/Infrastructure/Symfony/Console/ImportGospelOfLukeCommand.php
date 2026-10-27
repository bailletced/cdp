<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextResult;
use App\Chain\Application\DistributeTexts;
use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\TextSource;
use App\Chain\Domain\ValueObject\TextTitle;
use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Imports the Gospel according to Luke (GospelOfLuke) into the library, via
 * the command bus so each reading goes through the same sanitizing as a text
 * typed by an organizer. A reading already in the library (same title and
 * source) is reused: the command can run again, for another edition.
 *
 * With --edition, the 48 readings are spread over the slots of that edition,
 * in order: the first slot opens the gospel, the last one closes it.
 */
#[AsCommand(name: 'app:chain:import-gospel-of-luke', description: 'Importe l’évangile selon saint Luc (Louis Segond 1910) en 48 lectures, et peut les répartir sur une édition')]
final class ImportGospelOfLukeCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TextRepositoryInterface $texts,
        private EditionRepositoryInterface $editions,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('edition', null, InputOption::VALUE_REQUIRED, 'Identifiant de l’édition dont les créneaux reçoivent les lectures');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $editionId = $this->edition($input->getOption('edition'));

        if (false === $editionId) {
            $output->writeln('<error>Édition introuvable : aucune lecture importée.</error>');

            return Command::FAILURE;
        }

        $library = [];
        foreach ($this->texts->findAll() as $text) {
            $library[self::key($text->getTitle()->value, $text->getSource()->value)] = $text->getId();
        }

        $textIds = [];
        $added = 0;
        foreach (GospelOfLuke::readings() as $reading) {
            $key = self::key($reading['title'], $reading['source']);

            if (!isset($library[$key])) {
                $result = $this->commandBus->dispatch(new CreateText(
                    new TextTitle($reading['title']),
                    new TextSource($reading['source']),
                    $reading['body'],
                    null,
                ));

                if (!$result instanceof CreateTextResult) {
                    throw new \LogicException(\sprintf('Expected the command bus to return a %s, got %s.', CreateTextResult::class, get_debug_type($result)));
                }

                $library[$key] = $result->textId;
                ++$added;
            }

            $textIds[] = $library[$key];
        }

        $output->writeln(\sprintf('<info>%d lecture%s ajoutée%2$s à la bibliothèque.</info>', $added, $added > 1 ? 's' : ''));

        if (null !== $editionId) {
            $this->commandBus->dispatch(new DistributeTexts($editionId, $textIds, 1));
            $output->writeln(\sprintf('<info>Lectures réparties sur les 48 créneaux de l’édition %s.</info>', (string) $editionId));
        }

        return Command::SUCCESS;
    }

    /**
     * The edition of the option: null without the option, false when unknown.
     */
    private function edition(mixed $option): EditionId|false|null
    {
        if (null === $option) {
            return null;
        }

        if (!\is_string($option) || !Uuid::isValid($option)) {
            return false;
        }

        return $this->editions->findById(new EditionId($option))?->getId() ?? false;
    }

    private static function key(string $title, string $source): string
    {
        return $title."\n".$source;
    }
}
