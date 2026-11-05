<?php

declare(strict_types=1);

namespace App\Tests\Behat\Chain;

use App\Chain\Domain\Repository\EditionRepositoryInterface;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Infrastructure\Symfony\Console\GospelOfMark;
use App\Chain\Infrastructure\Symfony\Console\ImportGospelOfMarkCommand;
use App\Tests\Behat\BehatKernelTrait;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

/**
 * Behat context for the Mark gospel loader command
 * (app:edition:load-mark-gospel): the 2026 content, 48 sections
 * distributed over the slots of an edition.
 */
final class ImportGospelOfMarkContext implements Context
{
    use BehatKernelTrait;

    #[Given('the Mark gospel has been imported on the last created edition')]
    #[When('I import the Mark gospel readings on the last created edition')]
    public function iLoadTheGospelOfMarkOnTheLastCreatedEdition(): void
    {
        $state = $this->state();
        $container = $state->kernel?->getContainer()->get('test.service_container');

        /** @var ImportGospelOfMarkCommand $command */
        $command = $container?->get(ImportGospelOfMarkCommand::class);

        $tester = new CommandTester($command);
        $state->lastCommandExitCode = $tester->execute([
            '--edition' => $state->capturedEditionId,
        ]);
        $state->lastCommandDisplay = $tester->getDisplay();
    }

    #[When('I import the Mark gospel readings on an unknown edition')]
    public function iLoadTheGospelOfMarkOnAnUnknownEdition(): void
    {
        $state = $this->state();
        $container = $state->kernel?->getContainer()->get('test.service_container');

        /** @var ImportGospelOfMarkCommand $command */
        $command = $container?->get(ImportGospelOfMarkCommand::class);

        $tester = new CommandTester($command);
        $state->lastCommandExitCode = $tester->execute([
            '--edition' => '00000000-0000-0000-0000-000000000000',
        ]);
        $state->lastCommandDisplay = $tester->getDisplay();
    }

    #[Then('the command should succeed')]
    public function theCommandShouldSucceed(): void
    {
        \assert(0 === $this->state()->lastCommandExitCode);
    }

    #[Then('the command should fail')]
    public function theCommandShouldFail(): void
    {
        \assert(1 === $this->state()->lastCommandExitCode);
    }

    #[Then('the command output should mention :word')]
    public function theCommandOutputShouldMention(string $word): void
    {
        \assert(str_contains((string) $this->state()->lastCommandDisplay, $word));
    }

    #[Then('every slot of the last created edition should carry its Mark reading, in order')]
    public function everySlotOfTheLastCreatedEditionShouldCarryItsMarkSectionInOrder(): void
    {
        $state = $this->state();
        $container = $state->kernel?->getContainer()->get('test.service_container');

        /** @var EditionRepositoryInterface $editionRepository */
        $editionRepository = $container?->get(EditionRepositoryInterface::class);

        /** @var \App\Chain\Domain\Entity\Edition $edition */
        $edition = $editionRepository->findById(
            new EditionId(Uuid::fromString((string) $state->capturedEditionId)),
        );
        \assert(null !== $edition);

        $slots = $edition->getSlots();
        $sections = GospelOfMark::readings();

        \assert(\count($slots) === \count($sections));

        foreach ($slots as $index => $slot) {
            \assert(null !== $slot->getTextId(), \sprintf('Slot rank %d carries no text.', $index + 1));
        }
    }

    #[Then('the library should contain :count readings of the Mark gospel')]
    public function theLibraryShouldContainSectionsOfTheMarkGospel(int $count): void
    {
        $state = $this->state();
        $container = $state->kernel?->getContainer()->get('test.service_container');

        /** @var \App\Chain\Domain\Repository\TextRepositoryInterface $textRepository */
        $textRepository = $container?->get(\App\Chain\Domain\Repository\TextRepositoryInterface::class);

        $markTexts = array_filter(
            $textRepository->findAll(),
            static fn (\App\Chain\Domain\Entity\Text $text): bool => str_contains($text->getSource()->value, 'Évangile selon saint Marc'),
        );

        \assert(\count($markTexts) === $count);
    }
}
