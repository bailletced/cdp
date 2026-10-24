<?php

declare(strict_types=1);

namespace App\Tests\Unit\Chain\Infrastructure\Symfony\Console;

use App\Chain\Application\CreateEdition;
use App\Chain\Application\CreateEditionHandler;
use App\Chain\Application\CreateText;
use App\Chain\Application\CreateTextHandler;
use App\Chain\Application\DistributeTexts;
use App\Chain\Application\DistributeTextsHandler;
use App\Chain\Application\ScheduleEdition;
use App\Chain\Application\ScheduleEditionHandler;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Slot;
use App\Chain\Domain\ValueObject\TextId;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryEditionRepository;
use App\Chain\Infrastructure\InMemory\Repository\InMemoryTextRepository;
use App\Chain\Infrastructure\Symfony\Console\LoadEditionFixturesCommand;
use App\Chain\Infrastructure\Symfony\Console\LoadTextFixturesCommand;
use App\Chain\Infrastructure\Symfony\HtmlSanitizer\SymfonyHtmlSanitizer;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use App\Shared\Domain\Time\ParisCalendar;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class LoadEditionFixturesCommandTest extends TestCase
{
    // Not on a half hour: the fixture dates must be rounded (RG-ED-2)
    private const string NOW = '2026-11-20 12:10:00';

    private InMemoryEditionRepository $editions;

    private InMemoryTextRepository $texts;

    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        $this->editions = new InMemoryEditionRepository();
        $this->texts = new InMemoryTextRepository();
        $clock = new MockClock(self::NOW, 'UTC');

        // The commands go through the real handlers
        $this->commandBus = new class($this->editions, $this->texts, $clock) implements CommandBusInterface {
            public function __construct(
                private readonly InMemoryEditionRepository $editions,
                private readonly InMemoryTextRepository $texts,
                private readonly MockClock $clock,
            ) {
            }

            #[\Override]
            public function dispatch(CommandInterface $command): mixed
            {
                return match (true) {
                    $command instanceof CreateEdition => new CreateEditionHandler($this->editions, new ParisCalendar())($command),
                    $command instanceof ScheduleEdition => new ScheduleEditionHandler($this->editions, $this->clock)($command),
                    $command instanceof DistributeTexts => new DistributeTextsHandler($this->editions, $this->texts)($command),
                    $command instanceof CreateText => new CreateTextHandler($this->texts, new SymfonyHtmlSanitizer())($command),
                    default => throw new \LogicException($command::class),
                };
            }
        };
    }

    public function testTheOpenEditionCarriesTheFortyEightTextsInTheirOrder(): void
    {
        new CommandTester(new LoadTextFixturesCommand($this->commandBus, $this->texts))->execute([]);

        self::assertSame(Command::SUCCESS, $this->execute()->getStatusCode());

        $open = $this->edition('24 heures de prière — édition en cours');
        $titles = array_map(fn (Slot $slot): ?string => $this->textTitle($slot->getTextId()?->toRfc4122()), $open->getSlots());
        self::assertNotContains(null, $titles, 'Every slot of the open edition has its text.');
        self::assertSame('Que rien ne te trouble', $titles[0]);
        self::assertSame('Prière d’abandon', $titles[47]);

        // The draft edition stays empty: the organizers can try the attribution on it
        $draft = $this->edition('24 heures de prière — prochaine édition');
        foreach ($draft->getSlots() as $slot) {
            self::assertNull($slot->getTextId());
        }
    }

    public function testTheEditionInProgressIsOpenToInscriptions(): void
    {
        $this->execute();

        $now = new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC'));
        self::assertSame('open', $this->edition('24 heures de prière — édition en cours')->statusAt($now));
        self::assertSame('draft', $this->edition('24 heures de prière — prochaine édition')->statusAt($now));
    }

    public function testAnEmptyLibraryLeavesTheSlotsWithoutText(): void
    {
        $tester = $this->execute();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Bibliothèque vide', $tester->getDisplay());
        foreach ($this->edition('24 heures de prière — édition en cours')->getSlots() as $slot) {
            self::assertNull($slot->getTextId());
        }
    }

    private function execute(): CommandTester
    {
        $tester = new CommandTester(new LoadEditionFixturesCommand($this->commandBus, new MockClock(self::NOW, 'UTC'), $this->texts));
        $tester->execute([]);

        return $tester;
    }

    private function edition(string $title): Edition
    {
        foreach ($this->editions->findAll() as $edition) {
            if ($title === $edition->getTitle()->value) {
                return $edition;
            }
        }

        self::fail(\sprintf('No edition « %s ».', $title));
    }

    private function textTitle(?string $textId): ?string
    {
        return null === $textId ? null : $this->texts->findById(new TextId($textId))?->getTitle()->value;
    }
}
